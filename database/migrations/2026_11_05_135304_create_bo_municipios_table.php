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
        Schema::create('bo_municipios', function (Blueprint $table) {
            $table->id('bo_municipios_id');
            $table->string('nombre', 50);
            $table->string('nombre_oficial', 100);
            $table->decimal('latitud', 10, 8)->nullable();
            $table->decimal('longitud', 11, 8)->nullable();

            $table->unsignedBigInteger('bo_provincias_id')->nullable();

            $table->foreign('bo_provincias_id')
                ->references('bo_provincias_id')
                ->on('bo_provincias')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bo_municipios');
    }
};
