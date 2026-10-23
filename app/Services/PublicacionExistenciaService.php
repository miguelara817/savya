<?php

namespace App\Services;

use App\Models\Existencia;
use App\Models\PublicacionExistencia;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PublicacionExistenciaService
{
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
