<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Inserta los estados del flujo operativo sin eliminar los estados existentes.
     */
    public function up(): void
    {
        $estados = [
            ['codigo' => 'PENDIENTE_APROBACION', 'nombre' => 'Pendiente de aprobación', 'descripcion' => 'Orden completa, a la espera de revisión.'],
            ['codigo' => 'APROBADA', 'nombre' => 'Aprobada', 'descripcion' => 'Orden aprobada con stock reservado.'],
            ['codigo' => 'PARCIALMENTE_ATENDIDA', 'nombre' => 'Parcialmente atendida', 'descripcion' => 'Orden con salidas o entregas parciales.'],
            ['codigo' => 'COMPLETADA', 'nombre' => 'Completada', 'descripcion' => 'Todas las cantidades fueron despachadas y entregadas.'],
            ['codigo' => 'RECHAZADA', 'nombre' => 'Rechazada', 'descripcion' => 'Orden rechazada durante la aprobación.'],
            ['codigo' => 'CANCELADA', 'nombre' => 'Cancelada', 'descripcion' => 'Orden cancelada y con reservas pendientes liberadas.'],
        ];

        foreach ($estados as $estado) {
            DB::table('orden_estados')->updateOrInsert(
                ['codigo' => $estado['codigo']],
                [
                    'nombre' => $estado['nombre'],
                    'descripcion' => $estado['descripcion'],
                ],
            );
        }
    }

    /**
     * No se eliminan estados porque pueden estar referenciados por órdenes existentes.
     */
    public function down(): void
    {
        //
    }
};
