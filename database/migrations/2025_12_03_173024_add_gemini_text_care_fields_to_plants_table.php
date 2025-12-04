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
            $table->text('watering_guide')->nullable()->after('care_tips');
            $table->text('sunlight_guide')->nullable()->after('watering_guide');
            $table->text('soil_guide')->nullable()->after('sunlight_guide');
            $table->text('temperature_guide')->nullable()->after('soil_guide');
            $table->text('care_summary')->nullable()->after('temperature_guide');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plants', function (Blueprint $table) {
            $table->dropColumn([
                'watering_guide',
                'sunlight_guide',
                'soil_guide',
                'temperature_guide',
                'care_summary',
            ]);
        });
    }
};
