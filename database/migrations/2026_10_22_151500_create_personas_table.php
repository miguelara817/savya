<?php

use App\PersonaTipo;
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
        Schema::create('personas', function (Blueprint $table) {
            $table->id('persona_id');
            $table->enum('persona_tipo',PersonaTipo::cases())->default(PersonaTipo::Natural->value);
            $table->string('nombre', 50);
            $table->string('apellido', 50);
            $table->string('carnet', 50)->unique();
            $table->string('nit', 50);
            $table->boolean('is_active')->default(true)->notNull();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};
