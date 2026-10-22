<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stock_saldos', function (Blueprint $table) {
            $table->id('stock_saldo_id');
            $table->unsignedInteger('producto_id');
            $table->unsignedInteger('almacen_id');
            $table->integer('cantidad_actual')->default(0);
            $table->integer('cantidad_reservada')->default(0);
            $table->timestamp('updated_at')->useCurrent();

            $table->foreign('producto_id')
                ->references('producto_id')
                ->on('productos');
            $table->foreign('almacen_id')
                ->references('almacen_id')
                ->on('almacenes');

            $table->unique(['producto_id', 'almacen_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_saldos');
    }
};
