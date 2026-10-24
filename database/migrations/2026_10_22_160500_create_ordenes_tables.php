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
        Schema::create('ordenes', function (Blueprint $table) {
            $table->id('orden_id');
            $table->uuid('codigo_orden')->nullable();
            $table->string('numero', 30)->nullable();

            $table->foreignId('persona_id')
                ->nullable()
                ->constrained('personas', 'persona_id')
                ->nullOnDelete();

            $table->string('razon_social', 200);
            $table->string('nit', 30);
            $table->string('direccion_facturacion', 250)->nullable();
            $table->string('correo_facturacion', 150)->nullable();

            $table->unsignedInteger('almacen_id');
            $table->unsignedInteger('orden_estado_id');
            $table->foreignId('solicitante_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('fecha_registro')->useCurrent();
            $table->timestamp('fecha_efectivizacion')->nullable();
            $table->text('observaciones')->nullable();

            $table->unsignedInteger('cantidad_total');
            $table->decimal('precio_total', 12, 2)->default(0);

            // $table->string('firma_cliente_nombre', 200)->nullable();
            // $table->string('firma_cliente_documento', 50)->nullable();
            // $table->string('firma_cliente_archivo', 500)->nullable();
            // $table->timestamp('firmado_en')->nullable();

            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();

            $table->foreign('almacen_id')
                ->references('almacen_id')
                ->on('almacenes');
            $table->foreign('orden_estado_id')
                ->references('orden_estado_id')
                ->on('orden_estados');

            $table->index('orden_estado_id');
            $table->index('almacen_id');
            $table->index('persona_id');
            $table->index('fecha_registro');
        });

        Schema::create('orden_productos', function (Blueprint $table) {
            $table->id('orden_producto_id');
            $table->foreignId('orden_id')
                ->constrained('ordenes', 'orden_id')
                ->cascadeOnDelete();
            $table->foreignId('existencia_id')
                ->constrained('existencias', 'existencia_id');
            $table->unsignedInteger('producto_id');
            $table->integer('cantidad_solicitada');
            $table->decimal('precio_unitario', 12, 2);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('producto_id')
                ->references('producto_id')
                ->on('productos');

            $table->unique(['orden_id', 'producto_id']);
            $table->index('existencia_id');
        });

        Schema::create('orden_personas', function (Blueprint $table) {
            $table->id('orden_persona_id');
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('carnet', 30);
            $table->string('correo', 150);
            $table->string('telefono', 30);
            $table->foreignId('orden_id')->nullable()
                ->constrained('ordenes', 'orden_id')
                ->cascadeOnDelete();
            $table->foreignId('persona_id')
                ->constrained('personas', 'persona_id')
                ->cascadeOnDelete();
            $table->string('funcion', 40);
            $table->timestamp('created_at')->useCurrent();

            $table->unique(['orden_id', 'persona_id', 'funcion']);
        });

        Schema::create('orden_historial_estados', function (Blueprint $table) {
            $table->id('orden_historial_estado_id');
            $table->foreignId('orden_id')
                ->constrained('ordenes', 'orden_id')
                ->cascadeOnDelete();
            $table->unsignedInteger('orden_estado_id');
            $table->foreignId('responsable_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->text('observaciones')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('orden_estado_id')
                ->references('orden_estado_id')
                ->on('orden_estados');

            $table->index(['orden_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orden_historial_estados');
        Schema::dropIfExists('orden_personas');
        Schema::dropIfExists('orden_productos');
        Schema::dropIfExists('ordenes');
    }
};
