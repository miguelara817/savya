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
        Schema::create('movimientos', function (Blueprint $table) {
            $table->id('movimiento_id');
            $table->string('numero', 30)->unique();
            $table->string('tipo', 30);
            $table->string('estado', 20)->default('BORRADOR');
            $table->timestamp('fecha_movimiento')->useCurrent();

            $table->unsignedInteger('almacen_origen_id')->nullable();
            $table->unsignedInteger('almacen_destino_id')->nullable();

            $table->foreignId('orden_id')
                ->nullable()
                ->constrained('ordenes', 'orden_id')
                ->nullOnDelete();
            $table->foreignId('salida_id')
                ->nullable()
                ->unique()
                ->constrained('salidas_almacen', 'salida_id')
                ->nullOnDelete();
            $table->foreignId('responsable_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('observaciones')->nullable();
            $table->timestamp('confirmado_en')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('almacen_origen_id')
                ->references('almacen_id')
                ->on('almacenes');
            $table->foreign('almacen_destino_id')
                ->references('almacen_id')
                ->on('almacenes');

            $table->index('tipo');
            $table->index('estado');
            $table->index('fecha_movimiento');
            $table->index('orden_id');
        });

        Schema::create('movimiento_detalles', function (Blueprint $table) {
            $table->id('movimiento_detalle_id');
            $table->foreignId('movimiento_id')
                ->constrained('movimientos', 'movimiento_id')
                ->cascadeOnDelete();
            $table->unsignedInteger('producto_id');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 12, 2)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('producto_id')
                ->references('producto_id')
                ->on('productos');

            $table->unique(['movimiento_id', 'producto_id']);
            $table->index('producto_id');
        });

        Schema::create('movimiento_historial_estados', function (Blueprint $table) {
            $table->id('movimiento_historial_id');
            $table->foreignId('movimiento_id')
                ->constrained('movimientos', 'movimiento_id')
                ->cascadeOnDelete();
            $table->string('estado', 20);
            $table->foreignId('responsable_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['movimiento_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movimiento_historial_estados');
        Schema::dropIfExists('movimiento_detalles');
        Schema::dropIfExists('movimientos');
    }
};
