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
        Schema::create('plants', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\PlantCategory::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(\App\Models\ConservationStatus::class)->constrained()->cascadeOnDelete()
            $table->foreignIdFor(\App\Models\PlantingRecommendation::class)->constrained()->cascadeOnDelete()
            $table->string('common_name', 100);
            $table->string('scientific_name', 150)->unique();
            $table->string('family', 100);
            $table->string('habitat', 100);
            $table->string('lifespan',50);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plants');
    }
};
