<?php

namespace App\Services;

use App\Models\Existencia;
use App\Models\PublicacionExistencia;
use App\Models\StockSaldo;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PublicacionExistenciaService
{
    /**
     * Confirma el documento, suma las unidades al stock físico y fija el precio de venta vigente.
     */
    public function registrarCargaInicial(PublicacionExistencia $publicacion, User $usuario): void
    {
        $this->confirmarIngreso($publicacion, $usuario);
    }

    /**
     * Suma unidades y fija el precio de venta sin modificar las reservas ni las órdenes existentes.
     */
    public function confirmarIngreso(PublicacionExistencia $publicacion, User $usuario): void
    {
        DB::transaction(function () use ($publicacion, $usuario): void {
            $publicacion = PublicacionExistencia::query()->whereKey($publicacion->getKey())->lockForUpdate()->firstOrFail();

            Gate::forUser($usuario)->authorize('publicar', $publicacion);

            $this->publicar($publicacion, $usuario);

            $existencias = $publicacion->existencias()
                ->with(['producto', 'almacen'])
                ->orderBy('producto_id')
                ->orderBy('almacen_id')
                ->get();

            foreach ($existencias as $existencia) {
                if ($existencia->cantidad <= 0) {
                    throw new DomainException('Las cantidades ingresadas deben ser mayores que cero.');
                }

                $saldo = StockSaldo::query()
                    ->where('producto_id', $existencia->producto_id)
                    ->where('almacen_id', $existencia->almacen_id)
                    ->lockForUpdate()
                    ->first();

                if ($saldo === null) {
                    $saldo = StockSaldo::query()->create([
                        'producto_id' => $existencia->producto_id,
                        'almacen_id' => $existencia->almacen_id,
                        'cantidad_actual' => 0,
                        'cantidad_reservada' => 0,
                    ]);
                }

                $saldo->cantidad_actual += $existencia->cantidad;
                $saldo->precio_venta = $existencia->precio_unitario;
                $saldo->existencia_vigente_id = $existencia->getKey();
                $saldo->save();
            }
        }, 3);
    }

    /**
     * Publica una fotografía histórica de existencias sin modificar el stock operativo.
     */
    public function publicar(PublicacionExistencia $publicacion, User $usuario): void
    {
        DB::transaction(function () use ($publicacion, $usuario): void {
            $publicacion = PublicacionExistencia::query()
                ->whereKey($publicacion->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            Gate::forUser($usuario)->authorize('publicar', $publicacion);

            if ($publicacion->estado !== PublicacionExistencia::ESTADO_BORRADOR) {
                throw new DomainException('Solo se puede publicar una publicación en borrador.');
            }

            $existencias = Existencia::query()
                ->with(['producto', 'almacen'])
                ->where('publicacion_id', $publicacion->publicacion_id)
                ->orderBy('existencia_id')
                ->lockForUpdate()
                ->get();

            if ($existencias->isEmpty()) {
                throw new DomainException('La publicación debe tener al menos una línea de existencias.');
            }

            foreach ($existencias as $existencia) {
                if (! $existencia->producto?->is_active) {
                    throw new DomainException("El producto {$existencia->producto?->nombre} no está activo.");
                }

                if (! $existencia->almacen?->is_active) {
                    throw new DomainException("El almacén {$existencia->almacen?->nombre} no está activo.");
                }

                if ($existencia->cantidad < 0) {
                    throw new DomainException('Las cantidades publicadas no pueden ser negativas.');
                }

                if ((float) $existencia->precio_unitario < 0) {
                    throw new DomainException('Los precios publicados no pueden ser negativos.');
                }
            }

            $publicacion->estado = PublicacionExistencia::ESTADO_PUBLICADA;
            $publicacion->publicada_en = now();
            $publicacion->responsable_user_id = $usuario->getKey();
            $publicacion->save();
        }, 3);
    }
}
