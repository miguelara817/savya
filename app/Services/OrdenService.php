<?php

namespace App\Services;

use App\Enums\OrdenEstadoCodigo;
use App\Models\Entrega;
use App\Models\EntregaDetalle;
use App\Models\Orden;
use App\Models\OrdenEstado;
use App\Models\OrdenHistorialEstado;
use App\Models\OrdenProducto;
use App\Models\PublicacionExistencia;
use App\Models\ReservaStock;
use App\Models\SalidaAlmacen;
use App\Models\SalidaDetalle;
use App\Models\StockSaldo;
use App\Models\User;
use DomainException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OrdenService
{
    public function __construct(public OrdenPagoCorreoService $ordenPagoCorreoService) {}

    /**
     * Envía una orden en borrador a revisión.
     */
    public function enviarAprobacion(Orden $orden, User $usuario): void
    {
        DB::transaction(function () use ($orden, $usuario): void {
            $orden = $this->bloquearOrden($orden);

            Gate::forUser($usuario)->authorize('enviarAprobacion', $orden);

            $this->asegurarEstado($orden, [OrdenEstadoCodigo::Borrador]);
            $this->validarOrdenParaAprobacion($orden);

            $this->transicionar(
                $orden,
                OrdenEstadoCodigo::PendienteAprobacion,
                $usuario,
                'Orden enviada a aprobación.',
            );
        });
    }

    /**
     * Aprueba una orden y reserva el stock de todas sus líneas de forma atómica.
     */
    public function aprobar(Orden $orden, User $usuario, ?string $observacion = null): void
    {
        DB::transaction(function () use ($orden, $usuario, $observacion): void {
            $orden = $this->bloquearOrden($orden);

            Gate::forUser($usuario)->authorize('aprobar', $orden);

            $this->asegurarEstado($orden, [OrdenEstadoCodigo::PendienteAprobacion]);

            $productos = $this->validarOrdenParaAprobacion($orden);

            $saldos = StockSaldo::query()
                ->where('almacen_id', $orden->almacen_id)
                ->whereIn('producto_id', $productos->pluck('producto_id'))
                ->orderBy('producto_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('producto_id');

            foreach ($productos as $producto) {
                $saldo = $saldos->get($producto->producto_id);

                if (! $saldo instanceof StockSaldo) {
                    throw new DomainException(
                        "No existe un saldo de stock para el producto {$producto->producto->nombre} en el almacén seleccionado."
                    );
                }

                if ($saldo->disponible < $producto->cantidad_solicitada) {
                    throw new DomainException(
                        "Stock insuficiente para el producto {$producto->producto->nombre}. Disponible: {$saldo->disponible}, solicitado: {$producto->cantidad_solicitada}."
                    );
                }
            }

            foreach ($productos as $producto) {
                $saldo = $saldos->get($producto->producto_id);

                $saldo->cantidad_reservada += $producto->cantidad_solicitada;
                $saldo->save();

                ReservaStock::query()->updateOrCreate(
                    ['orden_producto_id' => $producto->orden_producto_id],
                    [
                        'producto_id' => $producto->producto_id,
                        'almacen_id' => $orden->almacen_id,
                        'cantidad' => $producto->cantidad_solicitada,
                        'estado' => ReservaStock::ESTADO_ACTIVA,
                        'reservada_en' => now(),
                        'liberada_en' => null,
                    ],
                );
            }

            $orden->fecha_efectivizacion = now();
            $orden->save();

            $this->transicionar(
                $orden,
                OrdenEstadoCodigo::Aprobada,
                $usuario,
                $observacion ?: 'Orden aprobada y stock reservado.',
            );
        }, 3);

        $orden->refresh();
        $this->ordenPagoCorreoService->enviar($orden);
    }

    public function registrarPago(Orden $orden, User $usuario, string $numeroBoleta, string $comprobante): void
    {
        DB::transaction(function () use ($orden, $usuario, $numeroBoleta, $comprobante): void {
            $orden = $this->bloquearOrden($orden);

            Gate::forUser($usuario)->authorize('pagar', $orden);
            $this->asegurarEstado($orden, [OrdenEstadoCodigo::Aprobada]);

            $orden->registrarPago($numeroBoleta, $comprobante);

            $this->transicionar(
                $orden,
                OrdenEstadoCodigo::Pagado,
                $usuario,
                'Pago registrado por el cliente. Boleta: '.$numeroBoleta,
            );
        }, 3);
    }

    public function verificarPago(Orden $orden, User $usuario, string $fechaTetativaEntrega): void
    {
        DB::transaction(function () use ($orden, $usuario, $fechaTetativaEntrega): void {
            $orden = $this->bloquearOrden($orden);

            Gate::forUser($usuario)->authorize('verificarPago', $orden);
            $this->asegurarEstado($orden, [OrdenEstadoCodigo::Pagado]);

            $fecha = Carbon::createFromFormat('!Y-m-d', $fechaTetativaEntrega);
            if (! $fecha || $fecha->format('Y-m-d') !== $fechaTetativaEntrega) {
                throw ValidationException::withMessages([
                    'fecha_pago' => 'La fecha de pago no es válida.',
                ]);
            }

            $orden->fecha_entrega_tentativa = $fecha->toDateString();
            $orden->save();

            $this->transicionar(
                $orden,
                OrdenEstadoCodigo::Verificado,
                $usuario,
                'Comprobante de pago verificado por ventas.',
            );
        }, 3);
    }

    public function rechazarPago(Orden $orden, User $usuario, string $motivo): void
    {
        if (blank($motivo)) {
            throw new DomainException('Debe registrar el motivo del rechazo del pago.');
        }

        $comprobante = null;

        DB::transaction(function () use ($orden, $usuario, $motivo, &$comprobante): void {
            $orden = $this->bloquearOrden($orden);

            Gate::forUser($usuario)->authorize('verificarPago', $orden);
            $this->asegurarEstado($orden, [OrdenEstadoCodigo::Pagado]);

            $comprobante = $orden->comprobante_pago;

            $orden->forceFill([
                'numero_boleta_pago' => null,
                'comprobante_pago' => null,
                'pagado_en' => null,
                'motivo_rechazo' => $motivo,
            ])->save();

            $this->transicionar(
                $orden,
                OrdenEstadoCodigo::Aprobada,
                $usuario,
                'Pago rechazado por ventas. Motivo: '.$motivo,
            );
        }, 3);

        if (filled($comprobante)) {
            Storage::disk('local')->delete($comprobante);
        }
    }

    /**
     * Rechaza una orden pendiente y libera cualquier reserva que pudiera existir.
     */
    public function rechazar(Orden $orden, User $usuario, string $motivo): void
    {
        if (blank($motivo)) {
            throw new DomainException('Debe registrar el motivo del rechazo.');
        }

        DB::transaction(function () use ($orden, $usuario, $motivo): void {
            $orden = $this->bloquearOrden($orden);

            Gate::forUser($usuario)->authorize('rechazar', $orden);

            $this->asegurarEstado($orden, [OrdenEstadoCodigo::PendienteAprobacion]);

            $this->liberarReservasPendientes($orden);
            $orden->motivo_rechazo = $motivo;

            $this->transicionar(
                $orden,
                OrdenEstadoCodigo::Rechazada,
                $usuario,
                "Orden rechazada. Motivo: {$motivo}",
            );
        }, 3);
    }

    /**
     * Cancela una orden y libera únicamente las reservas que aún no fueron despachadas.
     */
    public function cancelar(Orden $orden, User $usuario, ?string $motivo = null): void
    {
        DB::transaction(function () use ($orden, $usuario, $motivo): void {
            $orden = $this->bloquearOrden($orden);

            Gate::forUser($usuario)->authorize('cancelar', $orden);

            if ($orden->estaEnEstado(
                OrdenEstadoCodigo::Completada,
                OrdenEstadoCodigo::Rechazada,
                OrdenEstadoCodigo::Cancelada,
            )) {
                throw new DomainException('La orden ya se encuentra en un estado final y no puede cancelarse.');
            }

            $this->liberarReservasPendientes($orden);

            if ($orden->estaEnEstado(OrdenEstadoCodigo::Aprobada)) {
                $orden->motivo_rechazo = $motivo;
                $orden->save();
            }

            $observacion = $motivo
                ? "Orden cancelada. Motivo: {$motivo}"
                : 'Orden cancelada y reservas pendientes liberadas.';

            $this->transicionar(
                $orden,
                OrdenEstadoCodigo::Cancelada,
                $usuario,
                $observacion,
            );
        }, 3);
    }

    /**
     * Recalcula el estado de la orden a partir de las cantidades despachadas y entregadas.
     */
    public function actualizarEstadoPorCantidades(Orden $orden, User $usuario): void
    {
        $orden = $this->bloquearOrden($orden);

        if ($orden->estaEnEstado(
            OrdenEstadoCodigo::Completada,
            OrdenEstadoCodigo::Rechazada,
            OrdenEstadoCodigo::Cancelada,
        )) {
            return;
        }

        $productos = $orden->productos()->with('producto')->get();

        if ($productos->isEmpty()) {
            return;
        }

        $despachadoPorProducto = $this->cantidadesDespachadasPorProducto($orden);
        $entregadoPorProducto = $this->cantidadesEntregadasPorProducto($orden);

        $todoEntregado = $productos->every(
            fn (OrdenProducto $producto): bool => ($entregadoPorProducto[$producto->producto_id] ?? 0) >= $producto->cantidad_solicitada,
        );

        $hayMovimiento = $productos->contains(
            fn (OrdenProducto $producto): bool => ($despachadoPorProducto[$producto->producto_id] ?? 0) > 0
                || ($entregadoPorProducto[$producto->producto_id] ?? 0) > 0,
        );

        $estado = match (true) {
            $todoEntregado => OrdenEstadoCodigo::Completada,
            $hayMovimiento => OrdenEstadoCodigo::ParcialmenteAtendida,
            default => OrdenEstadoCodigo::Aprobada,
        };

        $this->transicionar(
            $orden,
            $estado,
            $usuario,
            'Estado actualizado automáticamente por salidas o entregas.',
        );
    }

    /**
     * Valida las líneas de una orden contra el producto, la publicación y el almacén.
     *
     * @return Collection<int, OrdenProducto>
     */
    public function validarOrdenParaAprobacion(Orden $orden): Collection
    {
        if (blank($orden->razon_social) || blank($orden->nit) || ! $orden->almacen_id) {
            throw new DomainException('La orden debe tener razón social, NIT y almacén antes de enviarse a aprobación.');
        }

        $productos = $orden->productos()
            ->with(['producto', 'existencia.publicacion'])
            ->orderBy('producto_id')
            ->get();

        if ($productos->isEmpty()) {
            throw new DomainException('La orden debe tener al menos una línea de producto.');
        }

        if ($productos->pluck('producto_id')->duplicates()->isNotEmpty()) {
            throw new DomainException('La orden no puede tener productos duplicados.');
        }

        foreach ($productos as $producto) {
            if ($producto->cantidad_solicitada <= 0) {
                throw new DomainException('Todas las cantidades solicitadas deben ser mayores que cero.');
            }

            if ((float) $producto->precio_unitario < 0) {
                throw new DomainException('Los precios unitarios no pueden ser negativos.');
            }

            if (! $producto->producto?->is_active) {
                throw new DomainException("El producto {$producto->producto?->nombre} no está activo.");
            }

            if (! $producto->existencia) {
                throw new DomainException('Una de las líneas no tiene una existencia publicada asociada.');
            }

            if ((int) $producto->existencia->producto_id !== (int) $producto->producto_id) {
                throw new DomainException('El producto de la línea no coincide con la existencia publicada.');
            }

            if ((int) $producto->existencia->almacen_id !== (int) $orden->almacen_id) {
                throw new DomainException('El almacén de la existencia no coincide con el almacén de la orden.');
            }

            if ($producto->existencia->publicacion?->estado !== PublicacionExistencia::ESTADO_PUBLICADA) {
                throw new DomainException('La publicación de existencias asociada debe estar publicada.');
            }
        }

        return $productos;
    }

    /**
     * @return array<int, int>
     */
    private function cantidadesDespachadasPorProducto(Orden $orden): array
    {
        return SalidaDetalle::query()
            ->join('salidas_almacen', 'salidas_almacen.salida_id', '=', 'salida_detalles.salida_id')
            ->join('orden_productos', 'orden_productos.orden_producto_id', '=', 'salida_detalles.orden_producto_id')
            ->where('salidas_almacen.orden_id', $orden->orden_id)
            ->where('salidas_almacen.estado', SalidaAlmacen::ESTADO_CONFIRMADA)
            ->selectRaw('orden_productos.producto_id, SUM(salida_detalles.cantidad_despachada) as cantidad')
            ->groupBy('orden_productos.producto_id')
            ->pluck('cantidad', 'producto_id')
            ->map(fn (mixed $cantidad): int => (int) $cantidad)
            ->all();
    }

    /**
     * @return array<int, int>
     */
    private function cantidadesEntregadasPorProducto(Orden $orden): array
    {
        return EntregaDetalle::query()
            ->join('entregas', 'entregas.entrega_id', '=', 'entrega_detalles.entrega_id')
            ->join('salida_detalles', 'salida_detalles.salida_detalle_id', '=', 'entrega_detalles.salida_detalle_id')
            ->join('salidas_almacen', 'salidas_almacen.salida_id', '=', 'salida_detalles.salida_id')
            ->join('orden_productos', 'orden_productos.orden_producto_id', '=', 'salida_detalles.orden_producto_id')
            ->where('salidas_almacen.orden_id', $orden->orden_id)
            ->where('entregas.estado', Entrega::ESTADO_CONFIRMADA)
            ->selectRaw('orden_productos.producto_id, SUM(entrega_detalles.cantidad_entregada) as cantidad')
            ->groupBy('orden_productos.producto_id')
            ->pluck('cantidad', 'producto_id')
            ->map(fn (mixed $cantidad): int => (int) $cantidad)
            ->all();
    }

    private function bloquearOrden(Orden $orden): Orden
    {
        return Orden::query()
            ->whereKey($orden->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    /**
     * @param  array<int, OrdenEstadoCodigo>  $estadosPermitidos
     */
    private function asegurarEstado(Orden $orden, array $estadosPermitidos): void
    {
        if (! $orden->estaEnEstado(...$estadosPermitidos)) {
            throw new DomainException('La orden no se encuentra en un estado válido para realizar esta operación.');
        }
    }

    private function transicionar(
        Orden $orden,
        OrdenEstadoCodigo $estadoCodigo,
        User $usuario,
        ?string $observacion = null,
    ): void {
        if ($orden->estadoCodigo() === $estadoCodigo->value) {
            return;
        }

        $estado = OrdenEstado::query()
            ->where('codigo', $estadoCodigo->value)
            ->firstOrFail();

        $orden->orden_estado_id = $estado->orden_estado_id;
        $orden->save();
        $orden->unsetRelation('estado');

        OrdenHistorialEstado::query()->create([
            'orden_id' => $orden->orden_id,
            'orden_estado_id' => $estado->orden_estado_id,
            'responsable_user_id' => $usuario->getKey(),
            'observaciones' => $observacion,
        ]);
    }

    private function liberarReservasPendientes(Orden $orden): void
    {
        $productos = $orden->productos()
            ->orderBy('producto_id')
            ->lockForUpdate()
            ->get();

        if ($productos->isEmpty()) {
            return;
        }

        $saldos = StockSaldo::query()
            ->where('almacen_id', $orden->almacen_id)
            ->whereIn('producto_id', $productos->pluck('producto_id'))
            ->orderBy('producto_id')
            ->lockForUpdate()
            ->get()
            ->keyBy('producto_id');

        $despachadoPorProducto = $this->cantidadesDespachadasPorProducto($orden);

        foreach ($productos as $producto) {
            $reserva = ReservaStock::query()
                ->where('orden_producto_id', $producto->orden_producto_id)
                ->lockForUpdate()
                ->first();

            if (! $reserva instanceof ReservaStock || $reserva->estado === ReservaStock::ESTADO_LIBERADA) {
                continue;
            }

            $despachado = $despachadoPorProducto[$producto->producto_id] ?? 0;
            $pendiente = max(0, $producto->cantidad_solicitada - $despachado);

            if ($pendiente > 0) {
                $saldo = $saldos->get($producto->producto_id);

                if ($saldo instanceof StockSaldo) {
                    $saldo->cantidad_reservada = max(0, $saldo->cantidad_reservada - $pendiente);
                    $saldo->save();
                }
            }

            $reserva->estado = ReservaStock::ESTADO_LIBERADA;
            $reserva->liberada_en = now();
            $reserva->save();
        }
    }
}
