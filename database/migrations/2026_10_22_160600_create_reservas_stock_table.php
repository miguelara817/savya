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
        Schema::create('reservas_stock', function (Blueprint $table) {
            $table->id('reserva_stock_id');
            $table->foreignId('orden_producto_id')
                ->unique()
                ->constrained('orden_productos', 'orden_producto_id')
                ->cascadeOnDelete();
            $table->unsignedInteger('producto_id');
            $table->unsignedInteger('almacen_id');
            $table->integer('cantidad');
            $table->string('estado', 20)->default('ACTIVA');
            $table->timestamp('reservada_en')->useCurrent();
            $table->timestamp('liberada_en')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('producto_id')
                ->references('producto_id')
                ->on('productos');
            $table->foreign('almacen_id')
                ->references('almacen_id')
                ->on('almacenes');

            $table->index(['producto_id', 'almacen_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas_stock');
    }
};
