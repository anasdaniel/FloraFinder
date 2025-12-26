<?php

use App\Models\User;
use App\Models\ForumThread;
use App\Models\ForumTag;
use App\Models\ForumPost;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\delete;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('user can filter forum threads by category', function () {
    $user = User::factory()->create();
    ForumThread::factory()->create(['category' => 'General', 'title' => 'General Thread']);
    ForumThread::factory()->create(['category' => 'Identification', 'title' => 'ID Thread']);

    actingAs($user)
        ->get(route('forum', ['category' => 'Identification']))
        ->assertStatus(200)
        ->assertSee('ID Thread')
        ->assertDontSee('General Thread');
});

test('user can toggle like on reaching a thread', function () {
    $user = User::factory()->create();
    $thread = ForumThread::factory()->create(['likes_count' => 0]);

    actingAs($user)
        ->post(route('forum.like', $thread))
        ->assertStatus(200)
        ->assertJson(['is_liked' => true, 'likes_count' => 1]);

    $this->assertDatabaseHas('thread_likes', [
        'user_id' => $user->id,
        'forum_thread_id' => $thread->id,
    ]);

    actingAs($user)
        ->post(route('forum.like', $thread))
        ->assertStatus(200)
        ->assertJson(['is_liked' => false, 'likes_count' => 0]);

    $this->assertDatabaseMissing('thread_likes', [
        'user_id' => $user->id,
        'forum_thread_id' => $thread->id,
    ]);
});

test('user can add and remove tags on their own thread', function () {
    $user = User::factory()->create();
    $thread = ForumThread::factory()->create(['user_id' => $user->id]);
    $tag = ForumTag::factory()->create();

    actingAs($user)
        ->post(route('forum.tags.add', $thread), ['tag_id' => $tag->id])
        ->assertStatus(200);

    $this->assertDatabaseHas('thread_tag', [
        'forum_thread_id' => $thread->id,
        'forum_tag_id' => $tag->id,
    ]);

    actingAs($user)
        ->delete(route('forum.tags.remove', [$thread, $tag]))
        ->assertStatus(200);

    $this->assertDatabaseMissing('thread_tag', [
        'forum_thread_id' => $thread->id,
        'forum_tag_id' => $tag->id,
    ]);
});

test('user cannot add tags to others threads', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $thread = ForumThread::factory()->create(['user_id' => $otherUser->id]);
    $tag = ForumTag::factory()->create();

    actingAs($user)
        ->post(route('forum.tags.add', $thread), ['tag_id' => $tag->id])
        ->assertStatus(403);
});

test('user can delete their own thread', function () {
    $user = User::factory()->create();
    $thread = ForumThread::factory()->create(['user_id' => $user->id]);

    actingAs($user)
        ->delete(route('forum.destroy', $thread))
        ->assertRedirect();

    $this->assertDatabaseMissing('forum_threads', ['id' => $thread->id]);
});

test('user cannot delete others threads', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $thread = ForumThread::factory()->create(['user_id' => $otherUser->id]);

    actingAs($user)
        ->delete(route('forum.destroy', $thread))
        ->assertStatus(403);
});

test('user can share a thread', function () {
    $user = User::factory()->create();
    $thread = ForumThread::factory()->create(['shares_count' => 5]);

    actingAs($user)
        ->post(route('forum.share', $thread))
        ->assertStatus(200)
        ->assertJson(['shares_count' => 6]);
});
