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
            ['codigo' => 'APROBADA', 'nombre' => 'Aprobada, pago pendiente', 'descripcion' => 'Orden aprobada con stock reservado.'],
            ['codigo' => 'PAGADO', 'nombre' => 'Pagado', 'descripcion' => 'El cliente registró el pago y adjuntó el comprobante.'],
            ['codigo' => 'VERIFICADO', 'nombre' => 'Pago verificado', 'descripcion' => 'El encargado de ventas verificó el pago.'],
            ['codigo' => 'PARCIALMENTE_ATENDIDA', 'nombre' => 'Parcialmente atendida', 'descripcion' => 'Orden con salidas o entregas parciales.'],
            ['codigo' => 'COMPLETADA', 'nombre' => 'Completada', 'descripcion' => 'Todas las cantidades fueron despachadas y entregadas.'],
            ['codigo' => 'RECHAZADA', 'nombre' => 'Rechazada', 'descripcion' => 'Orden rechazada durante la aprobación.'],
            ['codigo' => 'CANCELADA', 'nombre' => 'Cancelada', 'descripcion' => 'Orden cancelada y con reservas pendientes liberadas.'],
        ];

        foreach ($estados as $estado) {
            OrdenEstado::updateOrCreate(
                ['codigo' => $estado['codigo']],
                $estado
            );
        }

        OrdenEstado::query()
            ->whereIn('codigo', ['PAGO_PENDIENTE', 'ENTREGADA', 'REGISTRADO', 'EFECTIVIZADO', 'ANULADO'])
            ->delete();
    }
}
