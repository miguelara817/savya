<?php

namespace Database\Seeders;

use App\Models\OrdenEstado;
use Illuminate\Database\Seeder;

class OrdenEstadoSeeder extends Seeder
{
    /**
     * Seed the order states.
     */
    public function run(): void
    {
        $estados = [
            ['codigo' => 'BORRADOR', 'nombre' => 'Borrador', 'descripcion' => 'Orden en edición, aún no enviada a aprobación.'],
            ['codigo' => 'PENDIENTE_APROBACION', 'nombre' => 'Pendiente de aprobación', 'descripcion' => 'Orden completa, a la espera de revisión.'],
            ['codigo' => 'APROBADA', 'nombre' => 'Aprobada', 'descripcion' => 'Orden aprobada con stock reservado.'],
            ['codigo' => 'PARCIALMENTE_ATENDIDA', 'nombre' => 'Parcialmente atendida', 'descripcion' => 'Orden con salidas o entregas parciales.'],
            ['codigo' => 'COMPLETADA', 'nombre' => 'Completada', 'descripcion' => 'Todas las cantidades fueron despachadas y entregadas.'],
            ['codigo' => 'RECHAZADA', 'nombre' => 'Rechazada', 'descripcion' => 'Orden rechazada durante la aprobación.'],
            ['codigo' => 'CANCELADA', 'nombre' => 'Cancelada', 'descripcion' => 'Orden cancelada y con reservas pendientes liberadas.'],
            ['codigo' => 'REGISTRADO', 'nombre' => 'Registrado', 'descripcion' => 'Estado heredado: pedido registrado.'],
            ['codigo' => 'EFECTIVIZADO', 'nombre' => 'Efectivizado', 'descripcion' => 'Estado heredado: pedido efectivizado.'],
            ['codigo' => 'ANULADO', 'nombre' => 'Anulado', 'descripcion' => 'Estado heredado: pedido anulado sin efecto en el inventario.'],
        ];

        foreach ($estados as $estado) {
            OrdenEstado::updateOrCreate(
                ['codigo' => $estado['codigo']],
                $estado
            );
        }
    }
}
