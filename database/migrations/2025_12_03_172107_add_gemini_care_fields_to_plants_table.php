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
            // Track the source of care details (gemini, trefle, or null)
            $table->string('care_source', 20)->nullable()->after('care_cached_at');

            // Gemini-specific fields that Trefle doesn't provide
            $table->string('watering_frequency')->nullable()->after('soil_humidity');
            $table->text('care_tips')->nullable()->after('watering_frequency');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plants', function (Blueprint $table) {
            $table->dropColumn([
                'care_source',
                'watering_frequency',
                'care_tips',
            ]);
        });
    }
};