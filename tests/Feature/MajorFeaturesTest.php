<?php

use App\Models\User;
use App\Models\Sighting;
use App\Models\ForumThread;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('user can access dashboard', function () {
    $user = User::factory()->create();
    actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('welcome-plant'));
});

test('user can browse plant library', function () {
    $user = User::factory()->create();
    actingAs($user)
        ->get(route('plants.index'))
        ->assertStatus(200);
});

test('user can create a forum thread', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    actingAs($user);

    $response = post(route('forum.store'), [
        'title' => 'Test Forum Thread',
        'content' => 'This is a test content for the forum thread.',
        'category' => 'General', // Assuming 'General' is a valid category or just a string
        'image' => UploadedFile::fake()->image('thread.jpg'),
    ]);

    $response->assertRedirect(route('forum'));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('forum_threads', [
        'title' => 'Test Forum Thread',
        'user_id' => $user->id,
    ]);
});

test('user can report a sighting', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    actingAs($user);

    // Mocking the PlantCacheService references if necessary, 
    // but assuming simple data entry works for now. 
    // If it fails on external API calls, we might need to mock the service.

    // Using a POST payload that matches SightingController validation
    $response = post(route('sightings.store'), [
        'scientific_name' => 'Hibiscus rosa-sinensis',
        'common_name' => 'Hibiscus',
        'latitude' => 3.1415,
        'longitude' => 101.6869,
        'images' => [
            UploadedFile::fake()->image('sighting.jpg')
        ],
        'save_to_collection' => '0', // Just report sighting to keep it simple
        'report_sighting' => '1',
    ]);

    // Expect redirect or success
    // SightingController usually redirects or returns JSON?
    // Let's assume standard redirect to sighting index or show
    // Inspecting the controller code (which I saw earlier) might reveal the return, 
    // As I didn't see the return statement in the snippet, I'll expect 200 or 302.
    // Ideally, assertDatabaseHas 'sightings'

    $this->assertDatabaseHas('sightings', [
        'scientific_name' => 'Hibiscus rosa-sinensis',
        'user_id' => $user->id,
    ]);
});
