<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordenes', function (Blueprint $table): void {
            $table->string('verificacion_token', 64)->nullable()->unique();
        });
    }

    public function down(): void
    {
        Schema::table('ordenes', function (Blueprint $table): void {
            $table->dropUnique(['verificacion_token']);
            $table->dropColumn('verificacion_token');
        });
    }
};
