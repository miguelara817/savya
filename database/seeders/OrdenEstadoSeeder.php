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
            ['codigo' => 'BORRADOR', 'nombre' => 'Borrador', 'descripcion' => 'Pedido en edición, aún no registrado.'],
            ['codigo' => 'REGISTRADO', 'nombre' => 'Registrado', 'descripcion' => 'Pedido registrado y pendiente de efectivización.'],
            ['codigo' => 'EFECTIVIZADO', 'nombre' => 'Efectivizado', 'descripcion' => 'Pedido confirmado y con stock reservado.'],
            ['codigo' => 'ANULADO', 'nombre' => 'Anulado', 'descripcion' => 'Pedido cancelado sin efecto en el inventario.'],
        ];

        foreach ($estados as $estado) {
            OrdenEstado::updateOrCreate(
                ['codigo' => $estado['codigo']],
                $estado
            );
        }
    }
}
