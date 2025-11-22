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
        Schema::create('forum_tags', function (Blueprint $table) {
            $table->id();
            $table->string('tag_name', 100)->unique();
            $table->string('tag_category', 100)->unique();
        });

        Schema::create('thread_tag', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\ForumThread::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(\App\Models\ForumTag::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('forum_tags');
        Schema::dropIfExists('thread_tag');
    }
};
