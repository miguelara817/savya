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
        Schema::create('entregas', function (Blueprint $table) {
            $table->id('entrega_id');
            $table->string('numero', 30)->unique();
            $table->foreignId('salida_id')
                ->constrained('salidas_almacen', 'salida_id');
            $table->string('estado', 30)->default('BORRADOR');
            $table->timestamp('fecha_entrega')->nullable();
            $table->foreignId('responsable_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('receptor_persona_id')
                ->nullable()
                ->constrained('personas', 'persona_id')
                ->nullOnDelete();
            $table->string('receptor_nombre', 200);
            $table->string('receptor_documento', 50)->nullable();
            $table->text('observaciones')->nullable();

            $table->string('firma_receptor_archivo', 500)->nullable();
            $table->timestamp('firmado_en')->nullable();

            $table->timestamp('confirmado_en')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->index('salida_id');
            $table->index('estado');
            $table->index('fecha_entrega');
        });

        Schema::create('entrega_detalles', function (Blueprint $table) {
            $table->id('entrega_detalle_id');
            $table->foreignId('entrega_id')
                ->constrained('entregas', 'entrega_id')
                ->cascadeOnDelete();
            $table->foreignId('salida_detalle_id')
                ->constrained('salida_detalles', 'salida_detalle_id');
            $table->integer('cantidad_entregada');
            $table->text('observaciones')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['entrega_id', 'salida_detalle_id']);
            $table->index('salida_detalle_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entrega_detalles');
        Schema::dropIfExists('entregas');
    }
};
