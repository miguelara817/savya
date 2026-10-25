<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $estadosRetirados = [
            'PAGO_PENDIENTE',
            'ENTREGADA',
            'REGISTRADO',
            'EFECTIVIZADO',
            'ANULADO',
        ];

        DB::transaction(function () use ($estadosRetirados): void {
            foreach ($estadosRetirados as $codigo) {
                $estado = DB::table('orden_estados')->where('codigo', $codigo)->first();

                if ($estado === null) {
                    continue;
                }

                $tieneOrdenes = DB::table('ordenes')
                    ->where('orden_estado_id', $estado->orden_estado_id)
                    ->exists();
                $tieneHistorial = DB::table('orden_historial_estados')
                    ->where('orden_estado_id', $estado->orden_estado_id)
                    ->exists();

                if ($tieneOrdenes || $tieneHistorial) {
                    throw new RuntimeException("No se puede retirar el estado {$codigo} porque aún tiene órdenes o historial asociado.");
                }

                DB::table('orden_estados')
                    ->where('orden_estado_id', $estado->orden_estado_id)
                    ->delete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('orden_estados')->insertOrIgnore([
            ['codigo' => 'PAGO_PENDIENTE', 'nombre' => 'Pendiente de pago', 'descripcion' => 'Orden aprobada, pendiente de pago'],
            ['codigo' => 'ENTREGADA', 'nombre' => 'Pedido entregado', 'descripcion' => 'Orden entregada'],
            ['codigo' => 'REGISTRADO', 'nombre' => 'Registrado', 'descripcion' => 'Estado heredado: pedido registrado.'],
            ['codigo' => 'EFECTIVIZADO', 'nombre' => 'Efectivizado', 'descripcion' => 'Estado heredado: pedido efectivizado.'],
            ['codigo' => 'ANULADO', 'nombre' => 'Anulado', 'descripcion' => 'Estado heredado: pedido anulado sin efecto en el inventario.'],
        ]);
    }
};
