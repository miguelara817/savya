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
        Schema::create('services_data_info', function (Blueprint $table) {
            $table->id();
            $table->string('service', 200);
            $table->string('base_url', 200);
            $table->string('api_key', 200);
            $table->string('session_id', 200);
            $table->string('api_key_header', 50);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('services_data_info');
    }
};
