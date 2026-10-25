<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes', function (Blueprint $table): void {
            $table->string('numero_boleta_pago', 100)->nullable();
            $table->string('comprobante_pago', 500)->nullable();
            $table->timestamp('pagado_en')->nullable();
        });

        DB::table('orden_estados')->updateOrInsert(
            ['codigo' => 'PAGADO'],
            [
                'nombre' => 'Pagado',
                'descripcion' => 'El cliente registró el pago y adjuntó el comprobante.',
            ],
        );

        DB::table('orden_estados')->updateOrInsert(
            ['codigo' => 'VERIFICADO'],
            [
                'nombre' => 'Pago verificado',
                'descripcion' => 'El encargado de ventas verificó el pago.',
            ],
        );
    }

    public function down(): void
    {
        Schema::table('ordenes', function (Blueprint $table): void {
            $table->dropColumn(['numero_boleta_pago', 'comprobante_pago', 'pagado_en']);
        });
    }
};
