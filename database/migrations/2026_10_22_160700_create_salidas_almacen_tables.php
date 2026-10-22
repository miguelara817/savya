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
        Schema::create('salidas_almacen', function (Blueprint $table) {
            $table->id('salida_id');
            $table->string('numero', 30)->unique();
            $table->foreignId('orden_id')
                ->constrained('ordenes', 'orden_id');
            $table->unsignedInteger('almacen_id');
            $table->string('estado', 30)->default('BORRADOR');
            $table->timestamp('fecha_salida')->nullable();
            $table->foreignId('responsable_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('observaciones')->nullable();

            $table->string('firma_responsable_nombre', 200)->nullable();
            $table->string('firma_responsable_archivo', 500)->nullable();
            $table->timestamp('firmado_en')->nullable();

            $table->timestamp('confirmado_en')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('almacen_id')
                ->references('almacen_id')
                ->on('almacenes');

            $table->index('orden_id');
            $table->index('almacen_id');
            $table->index('estado');
            $table->index('fecha_salida');
        });

        Schema::create('salida_detalles', function (Blueprint $table) {
            $table->id('salida_detalle_id');
            $table->foreignId('salida_id')
                ->constrained('salidas_almacen', 'salida_id')
                ->cascadeOnDelete();
            $table->foreignId('orden_producto_id')
                ->constrained('orden_productos', 'orden_producto_id');
            $table->integer('cantidad_despachada');
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['salida_id', 'orden_producto_id']);
            $table->index('orden_producto_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salida_detalles');
        Schema::dropIfExists('salidas_almacen');
    }
};
