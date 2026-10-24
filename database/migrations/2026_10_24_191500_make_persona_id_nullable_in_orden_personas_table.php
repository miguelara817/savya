<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orden_personas', function (Blueprint $table) {
            $table->dropForeign(['persona_id']);
            $table->foreignId('persona_id')->nullable()->change();
            $table->foreign('persona_id')->references('persona_id')->on('personas')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orden_personas', function (Blueprint $table) {
            $table->dropForeign(['persona_id']);
            $table->foreignId('persona_id')->nullable(false)->change();
            $table->foreign('persona_id')->references('persona_id')->on('personas')->cascadeOnDelete();
        });
    }
};
