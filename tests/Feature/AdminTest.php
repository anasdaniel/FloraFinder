<?php

use App\Models\User;
use App\Models\ForumThread;
use App\Models\ForumPost;
use App\Models\Plant;
use App\Models\Sighting;
use function Pest\Laravel\actingAs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('non-admin user cannot access admin dashboard', function () {
    $user = User::factory()->create(['is_admin' => false]);

    actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertStatus(403);
});

test('admin user can access admin dashboard', function () {
    $admin = User::factory()->create(['is_admin' => true]);

    actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertStatus(200);
});

test('admin can toggle user admin status', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $user = User::factory()->create(['is_admin' => false]);

    actingAs($admin)
        ->patch(route('admin.users.toggle-admin', $user))
        ->assertRedirect();

    $this->assertTrue($user->fresh()->is_admin);

    actingAs($admin)
        ->patch(route('admin.users.toggle-admin', $user))
        ->assertRedirect();

    $this->assertFalse($user->fresh()->is_admin);
});

test('admin can delete a forum thread', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $thread = ForumThread::factory()->create();

    actingAs($admin)
        ->delete(route('admin.forum.threads.destroy', $thread))
        ->assertRedirect();

    $this->assertDatabaseMissing('forum_threads', ['id' => $thread->id]);
});

test('admin can delete a sighting', function () {
    $admin = User::factory()->create(['is_admin' => true]);
    $sighting = Sighting::factory()->create();

    actingAs($admin)
        ->delete(route('admin.sightings.destroy', $sighting))
        ->assertRedirect();

    $this->assertDatabaseMissing('sightings', ['id' => $sighting->id]);
});
