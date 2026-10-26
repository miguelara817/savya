<?php

namespace App\Services;

use App\Models\Movimiento;
use App\Models\MovimientoDetalle;
use App\Models\MovimientoHistorialEstado;
use App\Models\Orden;
use App\Models\OrdenProducto;
use App\Models\ReservaStock;
use App\Models\SalidaAlmacen;
use App\Models\SalidaDetalle;
use App\Models\StockSaldo;
use App\Models\User;
use App\Support\DocumentoNumero;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class SalidaAlmacenService
{
    public function __construct(private OrdenService $ordenService) {}

    /**
     * Crea una salida en borrador con las cantidades pendientes de despacho de la orden.
     */
    public function prepararDesdeOrden(Orden $orden, User $usuario): SalidaAlmacen
    {
        return DB::transaction(function () use ($orden, $usuario): SalidaAlmacen {
            $orden = Orden::query()
                ->whereKey($orden->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (! $usuario->can('create', SalidaAlmacen::class) && ! $usuario->can('Entregar:Orden')) {
                Gate::forUser($usuario)->authorize('create', SalidaAlmacen::class);
            }

            if (! $orden->estaEnCurso()) {
                throw new DomainException('Solo se puede preparar una salida para una orden aprobada o parcialmente atendida.');
            }

            if (SalidaAlmacen::query()
                ->where('orden_id', $orden->orden_id)
                ->where('estado', SalidaAlmacen::ESTADO_BORRADOR)
                ->exists()) {
                throw new DomainException('Ya existe una salida en borrador para esta orden. Confirme o anule la salida existente antes de crear otra.');
            }

            $productos = $orden->productos()
                ->with('producto')
                ->orderBy('orden_producto_id')
                ->get();

            $despachadoPorLinea = $this->despachadoPorLinea($orden);

            $lineas = $productos
                ->map(function (OrdenProducto $producto) use ($despachadoPorLinea): array {
                    $despachado = $despachadoPorLinea[$producto->orden_producto_id] ?? 0;

                    return [
                        'orden_producto_id' => $producto->orden_producto_id,
                        'cantidad_despachada' => max(0, $producto->cantidad_solicitada - $despachado),
                    ];
                })
                ->filter(fn (array $linea): bool => $linea['cantidad_despachada'] > 0)
                ->values();

            if ($lineas->isEmpty()) {
                throw new DomainException('La orden no tiene cantidades pendientes de despacho.');
            }

            $salida = SalidaAlmacen::query()->create([
                'numero' => DocumentoNumero::generar('SAL'),
                'orden_id' => $orden->orden_id,
                'almacen_id' => $orden->almacen_id,
                'estado' => SalidaAlmacen::ESTADO_BORRADOR,
                'responsable_user_id' => $usuario->getKey(),
                'observaciones' => $orden->observaciones,
            ]);

            foreach ($lineas as $linea) {
                SalidaDetalle::query()->create([
                    'salida_id' => $salida->salida_id,
                    ...$linea,
                ]);
            }

            return $salida->load('detalles.ordenProducto.producto', 'orden', 'almacen');
        }, 3);
    }

    /**
     * Confirma la salida física, descuenta el stock y libera la reserva utilizada.
     */
    public function confirmar(SalidaAlmacen $salida, User $usuario): void
    {
        DB::transaction(function () use ($salida, $usuario): void {
            $salida = SalidaAlmacen::query()
                ->whereKey($salida->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($salida->estado === SalidaAlmacen::ESTADO_CONFIRMADA) {
                return;
            }

            Gate::forUser($usuario)->authorize('confirmar', $salida);

            if ($salida->estado !== SalidaAlmacen::ESTADO_BORRADOR) {
                throw new DomainException('Solo se puede confirmar una salida en borrador.');
            }

            $orden = Orden::query()
                ->whereKey($salida->orden_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $orden->estaEnCurso()) {
                throw new DomainException('La orden asociada no está aprobada o parcialmente atendida.');
            }

            if ((int) $salida->almacen_id !== (int) $orden->almacen_id) {
                throw new DomainException('El almacén de la salida no coincide con el almacén de la orden.');
            }

            $detalles = SalidaDetalle::query()
                ->where('salida_id', $salida->salida_id)
                ->orderBy('orden_producto_id')
                ->lockForUpdate()
                ->get();

            if ($detalles->isEmpty()) {
                throw new DomainException('La salida debe tener al menos una línea de producto.');
            }

            $ordenProductos = OrdenProducto::query()
                ->whereIn('orden_producto_id', $detalles->pluck('orden_producto_id'))
                ->orderBy('orden_producto_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('orden_producto_id');

            $despachadoPorLinea = $this->despachadoPorLinea($orden, $salida->salida_id);

            $cantidadesPorProducto = [];

            foreach ($detalles as $detalle) {
                $linea = $ordenProductos->get($detalle->orden_producto_id);

                if (! $linea instanceof OrdenProducto || (int) $linea->orden_id !== (int) $orden->orden_id) {
                    throw new DomainException('La salida contiene una línea que no pertenece a la orden asociada.');
                }

                if ($detalle->cantidad_despachada <= 0) {
                    throw new DomainException('Las cantidades despachadas deben ser mayores que cero.');
                }

                $despachado = $despachadoPorLinea[$linea->orden_producto_id] ?? 0;
                $pendienteAutorizado = $linea->cantidad_solicitada - $despachado;

                if ($detalle->cantidad_despachada > $pendienteAutorizado) {
                    throw new DomainException(
                        "La cantidad despachada del producto {$linea->producto?->nombre} supera la cantidad autorizada pendiente ({$pendienteAutorizado})."
                    );
                }

                $cantidadesPorProducto[$linea->producto_id] = ($cantidadesPorProducto[$linea->producto_id] ?? 0)
                    + $detalle->cantidad_despachada;
            }

            $saldos = StockSaldo::query()
                ->where('almacen_id', $orden->almacen_id)
                ->whereIn('producto_id', array_keys($cantidadesPorProducto))
                ->orderBy('producto_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('producto_id');

            foreach ($cantidadesPorProducto as $productoId => $cantidad) {
                $saldo = $saldos->get($productoId);

                if (! $saldo instanceof StockSaldo) {
                    throw new DomainException('No existe un saldo de stock para uno de los productos de la salida.');
                }

                if ($saldo->cantidad_actual < $cantidad) {
                    throw new DomainException('El stock físico es insuficiente para confirmar la salida.');
                }

                if ($saldo->cantidad_reservada < $cantidad) {
                    throw new DomainException('La reserva disponible es insuficiente para confirmar la salida.');
                }
            }

            foreach ($cantidadesPorProducto as $productoId => $cantidad) {
                $saldo = $saldos->get($productoId);

                $saldo->cantidad_actual -= $cantidad;
                $saldo->cantidad_reservada -= $cantidad;
                $saldo->save();
            }

            foreach ($detalles as $detalle) {
                $linea = $ordenProductos->get($detalle->orden_producto_id);
                $despachado = ($despachadoPorLinea[$linea->orden_producto_id] ?? 0) + $detalle->cantidad_despachada;

                $reserva = ReservaStock::query()
                    ->where('orden_producto_id', $linea->orden_producto_id)
                    ->lockForUpdate()
                    ->first();

                if ($reserva instanceof ReservaStock) {
                    if ($despachado >= $linea->cantidad_solicitada) {
                        $reserva->estado = ReservaStock::ESTADO_LIBERADA;
                        $reserva->liberada_en = now();
                    } else {
                        $reserva->estado = ReservaStock::ESTADO_PARCIAL;
                    }

                    $reserva->save();
                }
            }

            $movimiento = Movimiento::query()->firstOrCreate(
                ['salida_id' => $salida->salida_id],
                [
                    'numero' => DocumentoNumero::generar('MOV'),
                    'tipo' => Movimiento::TIPO_SALIDA,
                    'estado' => Movimiento::ESTADO_CONFIRMADO,
                    'fecha_movimiento' => now(),
                    'almacen_origen_id' => $salida->almacen_id,
                    'almacen_destino_id' => null,
                    'orden_id' => $orden->orden_id,
                    'responsable_user_id' => $usuario->getKey(),
                    'observaciones' => "Salida {$salida->numero} confirmada.",
                    'confirmado_en' => now(),
                ],
            );

            if ($movimiento->wasRecentlyCreated) {
                foreach ($cantidadesPorProducto as $productoId => $cantidad) {
                    $linea = $ordenProductos->firstWhere('producto_id', $productoId);

                    MovimientoDetalle::query()->create([
                        'movimiento_id' => $movimiento->movimiento_id,
                        'producto_id' => $productoId,
                        'cantidad' => $cantidad,
                        'precio_unitario' => $linea?->precio_unitario,
                    ]);
                }

                MovimientoHistorialEstado::query()->create([
                    'movimiento_id' => $movimiento->movimiento_id,
                    'estado' => Movimiento::ESTADO_CONFIRMADO,
                    'responsable_user_id' => $usuario->getKey(),
                    'observaciones' => 'Movimiento de salida confirmado.',
                ]);
            }

            $salida->estado = SalidaAlmacen::ESTADO_CONFIRMADA;
            $salida->fecha_salida ??= now();
            $salida->firmado_en ??= now();
            $salida->confirmado_en = now();
            $salida->responsable_user_id = $usuario->getKey();
            $salida->save();

            $this->ordenService->actualizarEstadoPorCantidades($orden, $usuario);
        }, 3);
    }

    /**
     * @return array<int, int>
     */
    private function despachadoPorLinea(Orden $orden, ?int $salidaExcluida = null): array
    {
        return SalidaDetalle::query()
            ->join('salidas_almacen', 'salidas_almacen.salida_id', '=', 'salida_detalles.salida_id')
            ->where('salidas_almacen.orden_id', $orden->orden_id)
            ->where('salidas_almacen.estado', SalidaAlmacen::ESTADO_CONFIRMADA)
            ->when($salidaExcluida, fn ($query) => $query->where('salidas_almacen.salida_id', '!=', $salidaExcluida))
            ->selectRaw('salida_detalles.orden_producto_id, SUM(salida_detalles.cantidad_despachada) as cantidad')
            ->groupBy('salida_detalles.orden_producto_id')
            ->pluck('cantidad', 'orden_producto_id')
            ->map(fn (mixed $cantidad): int => (int) $cantidad)
            ->all();
    }
}
