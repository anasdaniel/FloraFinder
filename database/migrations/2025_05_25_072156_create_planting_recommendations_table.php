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
        Schema::create('planting_recommendations', function (Blueprint $table) {
            $table->id();
            $table->string('soil_type', 100);
            $table->string('water_per_week', 100);
            $table->string('sunlight_per_week',100);
            $table->string('temperature_range', 100);
            $table->string('fertilizer_type', 100);
            $table->string('humidity_level', 100);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('planting_recommendations');
    }
};
