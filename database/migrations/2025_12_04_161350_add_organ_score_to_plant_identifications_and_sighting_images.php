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
        Schema::table('plant_identifications', function (Blueprint $table) {
            $table->unsignedTinyInteger('organ_score')->nullable()->after('organ')->comment('Organ detection confidence score (0-100%)');
        });

        Schema::table('sighting_images', function (Blueprint $table) {
            $table->unsignedTinyInteger('organ_score')->nullable()->after('organ')->comment('Organ detection confidence score (0-100%)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plant_identifications', function (Blueprint $table) {
            $table->dropColumn('organ_score');
        });

        Schema::table('sighting_images', function (Blueprint $table) {
            $table->dropColumn('organ_score');
        });
    }
};
