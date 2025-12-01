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
        Schema::create('forum_posts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('forum_thread_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('parent_post_id')->nullable();
            $table->text('content');
            $table->string('image')->nullable();
            $table->timestamps();

            $table->foreign('forum_thread_id')->references('id')->on('forum_threads')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('parent_post_id')->references('id')->on('forum_posts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_posts');
    }
};
