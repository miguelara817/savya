<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('publicaciones_existencias', function (Blueprint $table) {
            $table->string('tipo', 20)->default('CARGA_INICIAL');
        });

        Schema::table('stock_saldos', function (Blueprint $table) {
            $table->decimal('precio_venta', 12, 2)->nullable();
            $table->foreignId('existencia_vigente_id')->nullable()
                ->constrained('existencias', 'existencia_id')
                ->restrictOnDelete();
        });

        DB::table('stock_saldos')->orderBy('stock_saldo_id')->chunkById(200, function (Collection $saldos): void {
            foreach ($saldos as $saldo) {
                $existencia = DB::table('existencias')
                    ->join('publicaciones_existencias', 'publicaciones_existencias.publicacion_id', '=', 'existencias.publicacion_id')
                    ->where('existencias.producto_id', $saldo->producto_id)
                    ->where('existencias.almacen_id', $saldo->almacen_id)
                    ->where('publicaciones_existencias.estado', 'PUBLICADA')
                    ->whereNotNull('publicaciones_existencias.publicada_en')
                    ->orderByDesc('publicaciones_existencias.publicada_en')
                    ->orderByDesc('existencias.existencia_id')
                    ->first(['existencias.existencia_id', 'existencias.precio_unitario']);

                if ($existencia === null) {
                    continue;
                }

                DB::table('stock_saldos')->where('stock_saldo_id', $saldo->stock_saldo_id)->update([
                    'precio_venta' => $existencia->precio_unitario,
                    'existencia_vigente_id' => $existencia->existencia_id,
                ]);
            }
        }, 'stock_saldo_id');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_saldos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('existencia_vigente_id');
            $table->dropColumn('precio_venta');
        });

        Schema::table('publicaciones_existencias', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};
