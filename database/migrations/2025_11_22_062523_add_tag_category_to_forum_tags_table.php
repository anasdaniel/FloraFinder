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
        Schema::table('forum_tags', function (Blueprint $table) {
            if (!Schema::hasColumn('forum_tags', 'tag_category')) {
                $table->string('tag_category', 100)->after('tag_name');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forum_tags', function (Blueprint $table) {
            $table->dropColumn('tag_category');
        });
    }
};
