<?php

namespace App\Services;

use App\Enums\OrdenEstadoCodigo;
use App\Models\Entrega;
use App\Models\EntregaDetalle;
use App\Models\Orden;
use App\Models\ReservaStock;
use App\Models\SalidaAlmacen;
use App\Models\SalidaDetalle;
use App\Models\User;
use App\Support\DocumentoNumero;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class EntregaService
{
    public function __construct(
        private OrdenService $ordenService,
        private SalidaAlmacenService $salidaAlmacenService,
    ) {}

    /**
     * Completes dispatch and delivery for a verified order in one atomic operation.
     */
    public function confirmarOrdenVerificada(Orden $orden, User $usuario): void
    {
        DB::transaction(function () use ($orden, $usuario): void {
            $orden = Orden::query()
                ->whereKey($orden->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($usuario)->authorize('entregar', $orden);

            if (! $orden->estaEnEstado(OrdenEstadoCodigo::Verificado)) {
                throw new DomainException('Solo se puede confirmar como entregada una orden con el pago verificado.');
            }

            $salida = $this->salidaAlmacenService->prepararDesdeOrden($orden, $usuario);
            $this->salidaAlmacenService->confirmar($salida, $usuario);

            $entrega = $this->prepararDesdeSalida($salida->fresh(), $usuario);
            $this->confirmar($entrega, $usuario);

            $fechaLiberacion = $entrega->fresh()->fecha_entrega;
            $lineasOrden = $orden->productos()->pluck('orden_producto_id');
            $reservasLiberadas = ReservaStock::query()
                ->whereIn('orden_producto_id', $lineasOrden)
                ->update([
                    'estado' => ReservaStock::ESTADO_LIBERADA,
                    'liberada_en' => $fechaLiberacion,
                ]);

            if ($reservasLiberadas !== $lineasOrden->count()) {
                throw new DomainException('No fue posible liberar las reservas de stock para toda la orden.');
            }
        }, 3);
    }

    /**
     * Crea una entrega en borrador con las cantidades pendientes de recibir de la salida.
     */
    public function prepararDesdeSalida(
        SalidaAlmacen $salida,
        User $usuario,
        ?string $receptorNombre = null,
    ): Entrega {
        return DB::transaction(function () use ($salida, $usuario, $receptorNombre): Entrega {
            $salida = SalidaAlmacen::query()
                ->whereKey($salida->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($usuario)->authorize('create', Entrega::class);

            if ($salida->estado !== SalidaAlmacen::ESTADO_CONFIRMADA) {
                throw new DomainException('Solo se puede registrar una entrega para una salida confirmada.');
            }

            if (Entrega::query()
                ->where('salida_id', $salida->salida_id)
                ->where('estado', Entrega::ESTADO_BORRADOR)
                ->exists()) {
                throw new DomainException('Ya existe una entrega en borrador para esta salida. Confírmela o anúlela antes de crear otra.');
            }

            $detalles = SalidaDetalle::query()
                ->where('salida_id', $salida->salida_id)
                ->orderBy('salida_detalle_id')
                ->lockForUpdate()
                ->get();

            $entregadoPorDetalle = $this->entregadoPorDetalle($salida);

            $lineas = $detalles
                ->map(function (SalidaDetalle $detalle) use ($entregadoPorDetalle): array {
                    $entregado = $entregadoPorDetalle[$detalle->salida_detalle_id] ?? 0;

                    return [
                        'salida_detalle_id' => $detalle->salida_detalle_id,
                        'cantidad_entregada' => max(0, $detalle->cantidad_despachada - $entregado),
                    ];
                })
                ->filter(fn (array $linea): bool => $linea['cantidad_entregada'] > 0)
                ->values();

            if ($lineas->isEmpty()) {
                throw new DomainException('La salida no tiene cantidades pendientes de entrega.');
            }

            $orden = Orden::query()
                ->with('persona')
                ->findOrFail($salida->orden_id);

            $nombreReceptor = $receptorNombre
                ?: trim(($orden->persona?->nombre ?? '').' '.($orden->persona?->apellido ?? ''))
                ?: $orden->razon_social;

            $entrega = Entrega::query()->create([
                'numero' => DocumentoNumero::generar('ENT'),
                'salida_id' => $salida->salida_id,
                'estado' => Entrega::ESTADO_BORRADOR,
                'responsable_user_id' => $usuario->getKey(),
                'receptor_persona_id' => $orden->persona_id,
                'receptor_nombre' => $nombreReceptor,
                'observaciones' => $orden->observaciones,
            ]);

            foreach ($lineas as $linea) {
                EntregaDetalle::query()->create([
                    'entrega_id' => $entrega->entrega_id,
                    ...$linea,
                ]);
            }

            return $entrega->load('detalles.salidaDetalle.ordenProducto.producto', 'salida');
        }, 3);
    }

    /**
     * Confirma la entrega sin volver a descontar stock.
     */
    public function confirmar(Entrega $entrega, User $usuario): void
    {
        DB::transaction(function () use ($entrega, $usuario): void {
            $entrega = Entrega::query()
                ->whereKey($entrega->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($entrega->estado === Entrega::ESTADO_CONFIRMADA) {
                return;
            }

            Gate::forUser($usuario)->authorize('confirmar', $entrega);

            if ($entrega->estado !== Entrega::ESTADO_BORRADOR) {
                throw new DomainException('Solo se puede confirmar una entrega en borrador.');
            }

            $salida = SalidaAlmacen::query()
                ->whereKey($entrega->salida_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($salida->estado !== SalidaAlmacen::ESTADO_CONFIRMADA) {
                throw new DomainException('La salida asociada no está confirmada.');
            }

            $detalles = EntregaDetalle::query()
                ->where('entrega_id', $entrega->entrega_id)
                ->orderBy('salida_detalle_id')
                ->lockForUpdate()
                ->get();

            if ($detalles->isEmpty()) {
                throw new DomainException('La entrega debe tener al menos una línea de producto.');
            }

            $salidaDetalles = SalidaDetalle::query()
                ->whereIn('salida_detalle_id', $detalles->pluck('salida_detalle_id'))
                ->orderBy('salida_detalle_id')
                ->lockForUpdate()
                ->get()
                ->keyBy('salida_detalle_id');

            $entregadoPorDetalle = $this->entregadoPorDetalle($salida, $entrega->entrega_id);

            foreach ($detalles as $detalle) {
                $salidaDetalle = $salidaDetalles->get($detalle->salida_detalle_id);

                if (! $salidaDetalle instanceof SalidaDetalle || (int) $salidaDetalle->salida_id !== (int) $salida->salida_id) {
                    throw new DomainException('La entrega contiene una línea que no pertenece a la salida asociada.');
                }

                if ($detalle->cantidad_entregada <= 0) {
                    throw new DomainException('Las cantidades entregadas deben ser mayores que cero.');
                }

                $entregado = $entregadoPorDetalle[$detalle->salida_detalle_id] ?? 0;
                $pendientePorEntregar = $salidaDetalle->cantidad_despachada - $entregado;

                if ($detalle->cantidad_entregada > $pendientePorEntregar) {
                    throw new DomainException(
                        "La cantidad entregada supera la cantidad despachada pendiente ({$pendientePorEntregar})."
                    );
                }
            }

            $fechaConfirmacion = now();
            $entrega->estado = Entrega::ESTADO_CONFIRMADA;
            $entrega->fecha_entrega ??= $fechaConfirmacion;
            $entrega->firmado_en ??= $fechaConfirmacion;
            $entrega->confirmado_en = $fechaConfirmacion;
            $entrega->responsable_user_id = $usuario->getKey();
            $entrega->save();

            $orden = Orden::query()
                ->whereKey($salida->orden_id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->ordenService->actualizarEstadoPorCantidades($orden, $usuario);
        }, 3);
    }

    /**
     * @return array<int, int>
     */
    private function entregadoPorDetalle(SalidaAlmacen $salida, ?int $entregaExcluida = null): array
    {
        return EntregaDetalle::query()
            ->join('entregas', 'entregas.entrega_id', '=', 'entrega_detalles.entrega_id')
            ->join('salida_detalles', 'salida_detalles.salida_detalle_id', '=', 'entrega_detalles.salida_detalle_id')
            ->where('salida_detalles.salida_id', $salida->salida_id)
            ->where('entregas.estado', Entrega::ESTADO_CONFIRMADA)
            ->when($entregaExcluida, fn ($query) => $query->where('entregas.entrega_id', '!=', $entregaExcluida))
            ->selectRaw('entrega_detalles.salida_detalle_id, SUM(entrega_detalles.cantidad_entregada) as cantidad')
            ->groupBy('entrega_detalles.salida_detalle_id')
            ->pluck('cantidad', 'salida_detalle_id')
            ->map(fn (mixed $cantidad): int => (int) $cantidad)
            ->all();
    }
}
