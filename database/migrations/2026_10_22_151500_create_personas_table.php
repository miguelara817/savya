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
        Schema::create('persona_tipos', function (Blueprint $table) {
            $table->increments('persona_tipo_id');
            $table->string('valor', 30)->unique();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('personas', function (Blueprint $table) {
            $table->id('persona_id');
            $table->unsignedInteger('persona_tipo_id');
            $table->string('nombre', 100);
            $table->string('apellido', 100)->nullable();
            $table->string('carnet', 30)->nullable()->unique();
            $table->string('telefono', 30)->nullable();
            $table->string('correo', 150)->nullable();
            $table->string('direccion', 250)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();
            $table->softDeletes();

            $table->foreign('persona_tipo_id')
                ->references('persona_tipo_id')
                ->on('persona_tipos');

            $table->index(['nombre', 'apellido']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personas');
        Schema::dropIfExists('persona_tipos');
    }
};
