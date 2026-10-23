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
        Schema::create('publicaciones_existencias', function (Blueprint $table) {
            $table->id('publicacion_id');
            $table->string('numero', 30)->unique();
            $table->timestamp('fecha_ingreso');
            $table->foreignId('responsable_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('estado', 20)->default('BORRADOR');
            $table->text('observaciones')->nullable();
            $table->timestamp('publicada_en')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->index('fecha_ingreso');
            $table->index('estado');
        });

        Schema::create('existencias', function (Blueprint $table) {
            $table->id('existencia_id');
            $table->foreignId('publicacion_id')
                ->constrained('publicaciones_existencias', 'publicacion_id')
                ->cascadeOnDelete();
            $table->unsignedInteger('producto_id');
            $table->unsignedInteger('almacen_id');
            $table->integer('cantidad');
            $table->decimal('precio_unitario', 12, 2);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('producto_id')
                ->references('producto_id')
                ->on('productos');
            $table->foreign('almacen_id')
                ->references('almacen_id')
                ->on('almacenes');

            $table->unique(['publicacion_id', 'producto_id', 'almacen_id']);
            $table->index('producto_id');
            $table->index('almacen_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('existencias');
        Schema::dropIfExists('publicaciones_existencias');
    }
};
