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
        Schema::create('bo_provincias', function (Blueprint $table) {
            $table->id('bo_provincias_id');
            $table->string('codigo', 10);
            $table->string('nombre', 10);
            $table->string('latitud', 100);
            $table->string('longitud', 100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bo_provincias');
    }
};
