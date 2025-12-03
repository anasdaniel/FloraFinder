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
        Schema::table('sightings', function (Blueprint $table) {
            // Make existing foreign keys nullable
            $table->foreignId('plant_id')->nullable()->change();
            $table->foreignId('zone_id')->nullable()->change();

            // Add new columns for denormalized plant data
            $table->string('scientific_name')->nullable()->after('zone_id');
            $table->string('common_name')->nullable()->after('scientific_name');

            // Location data
            $table->decimal('latitude', 10, 7)->nullable()->after('common_name');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('location_name')->nullable()->after('longitude');
            $table->string('region')->nullable()->after('location_name');

            // Sighting date
            $table->dateTime('sighted_at')->nullable()->after('region');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sightings', function (Blueprint $table) {
            $table->dropColumn([
                'scientific_name',
                'common_name',
                'latitude',
                'longitude',
                'location_name',
                'region',
                'sighted_at',
            ]);
        });
    }
};
