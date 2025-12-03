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
        Schema::table('plants', function (Blueprint $table) {
            // Make existing required columns nullable for API-sourced plants
            $table->foreignId('planting_recommendation_id')->nullable()->change();
            $table->string('habitat', 100)->nullable()->change();
            $table->string('lifespan', 50)->nullable()->change();

            // Add genus column
            $table->string('genus', 100)->nullable()->after('family');

            // External database IDs
            $table->string('gbif_id')->nullable()->after('genus');
            $table->string('powo_id')->nullable()->after('gbif_id');
            $table->string('iucn_category', 50)->nullable()->after('powo_id');

            // Care details from Trefle API
            $table->text('description')->nullable()->after('iucn_category');
            $table->string('sowing')->nullable();
            $table->integer('days_to_harvest')->nullable();
            $table->integer('row_spacing_cm')->nullable();
            $table->integer('spread_cm')->nullable();
            $table->decimal('ph_minimum', 3, 1)->nullable();
            $table->decimal('ph_maximum', 3, 1)->nullable();
            $table->integer('light')->nullable(); // 0-10 scale
            $table->integer('atmospheric_humidity')->nullable(); // 0-10 scale
            $table->json('growth_months')->nullable();
            $table->json('bloom_months')->nullable();
            $table->json('fruit_months')->nullable();
            $table->integer('minimum_precipitation_mm')->nullable();
            $table->integer('maximum_precipitation_mm')->nullable();
            $table->integer('minimum_temperature_celsius')->nullable();
            $table->integer('maximum_temperature_celsius')->nullable();
            $table->integer('soil_nutriments')->nullable(); // 0-10 scale
            $table->integer('soil_salinity')->nullable(); // 0-10 scale
            $table->integer('soil_texture')->nullable(); // 0-10 scale
            $table->integer('soil_humidity')->nullable(); // 0-10 scale

            // Cache management
            $table->timestamp('care_cached_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plants', function (Blueprint $table) {
            $table->dropColumn([
                'genus',
                'gbif_id',
                'powo_id',
                'iucn_category',
                'description',
                'sowing',
                'days_to_harvest',
                'row_spacing_cm',
                'spread_cm',
                'ph_minimum',
                'ph_maximum',
                'light',
                'atmospheric_humidity',
                'growth_months',
                'bloom_months',
                'fruit_months',
                'minimum_precipitation_mm',
                'maximum_precipitation_mm',
                'minimum_temperature_celsius',
                'maximum_temperature_celsius',
                'soil_nutriments',
                'soil_salinity',
                'soil_texture',
                'soil_humidity',
                'care_cached_at',
            ]);
        });
    }
};
