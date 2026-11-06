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
        Schema::table('personas', function (Blueprint $table) {
            $table->unsignedBigInteger('bo_departamentos_id')->nullable();

            $table->foreign('bo_departamentos_id')
                ->references('bo_departamentos_id')
                ->on('bo_departamentos')
                ->nullOnDelete();

            $table->unsignedBigInteger('bo_provincias_id')->nullable();

            $table->foreign('bo_provincias_id')
                ->references('bo_provincias_id')
                ->on('bo_provincias')
                ->nullOnDelete();

            $table->unsignedBigInteger('bo_municipios_id')->nullable();

            $table->foreign('bo_municipios_id')
                ->references('bo_municipios_id')
                ->on('bo_municipios')
                ->nullOnDelete();

            $table->unsignedBigInteger('bo_localidades_id')->nullable();

            $table->foreign('bo_localidades_id')
                ->references('bo_localidades_id')
                ->on('bo_localidades')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('personas', function (Blueprint $table) {
            $table->dropForeign(['bo_departamentos_id']);
            $table->dropForeign(['bo_provincias_id']);
            $table->dropForeign(['bo_municipios_id']);
            $table->dropForeign(['bo_localidades_id']);

            $table->dropColumn([
                'bo_departamentos_id',
                'bo_provincias_id',
                'bo_municipios_id',
                'bo_localidades_id',
            ]);
        });
    }
};
