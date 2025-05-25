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
        Schema::create('zones', function (Blueprint $table) {
            $table->id();
            $table->string('zone_name', 100)->unique();
            $table->string('zone_type', 100);
            $table->string('location_description', 255);
        });

        Schema::create('plant_zone', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Plant::class)->constrained()->cascadeOnDelete()
            $table->foreignIdFor(\App\Models\Zone::class)->constrained()->cascadeOnDelete()
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('zones');
        Schema::dropIfExists('plant_zone');
    }
};
