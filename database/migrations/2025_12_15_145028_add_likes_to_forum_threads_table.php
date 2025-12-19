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
        // Add likes_count to forum_threads
        Schema::table('forum_threads', function (Blueprint $table) {
            $table->integer('likes_count')->default(0)->after('image');
            $table->integer('shares_count')->default(0)->after('likes_count');
        });

        // Create thread_likes pivot table
        Schema::create('thread_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\ForumThread::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(\App\Models\User::class)->constrained()->cascadeOnDelete();
            $table->timestamps();

            // Ensure a user can only like a thread once
            $table->unique(['forum_thread_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('forum_threads', function (Blueprint $table) {
            $table->dropColumn(['likes_count', 'shares_count']);
        });

        Schema::dropIfExists('thread_likes');
    }
};
