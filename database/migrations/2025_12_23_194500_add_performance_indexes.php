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
            $table->index('family');
            $table->index('genus');
            $table->index('iucn_category');
        });

        Schema::table('sightings', function (Blueprint $table) {
            $table->index('region');
            $table->index('sighted_at');
            $table->index('location_name');
        });

        Schema::table('plant_identifications', function (Blueprint $table) {
            $table->index('family');
            $table->index('genus');
            $table->index('iucn_category');
            $table->index('region');
        });

        Schema::table('forum_threads', function (Blueprint $table) {
            // category is currently text, but often used for filtering
            // In a real app, it might be better as a string(50) with an index
            // But let's index it if possible or change it to string later.
            // For now, let's skip category if it's text (limited index length)
        });
        
        Schema::table('forum_tags', function (Blueprint $table) {
            $table->index('tag_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plants', function (Blueprint $table) {
            $table->dropIndex(['family']);
            $table->dropIndex(['genus']);
            $table->index(['iucn_category']);
        });

        Schema::table('sightings', function (Blueprint $table) {
            $table->dropIndex(['region']);
            $table->dropIndex(['sighted_at']);
            $table->dropIndex(['location_name']);
        });

        Schema::table('plant_identifications', function (Blueprint $table) {
            $table->dropIndex(['family']);
            $table->dropIndex(['genus']);
            $table->dropIndex(['iucn_category']);
            $table->dropIndex(['region']);
        });

        Schema::table('forum_tags', function (Blueprint $table) {
            $table->dropIndex(['tag_category']);
        });
    }
};
