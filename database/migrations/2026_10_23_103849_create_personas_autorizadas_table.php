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
        Schema::create('personas_autorizadas', function (Blueprint $table) {
            $table->id('persona_autorizada_id');
            $table->string('nombre', 100);
            $table->string('apellido', 100);
            $table->string('carnet', 30)->unique();
            $table->string('correo', 150)->unique();
            $table->string('telefono', 30)->nullable();
            $table->boolean('usado')->default(false);
            $table->timestamp('usado_en')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->index(['correo', 'carnet']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personas_autorizadas');
    }
};
