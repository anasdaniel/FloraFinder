<?php

use App\Models\User;
use App\Models\Sighting;
use App\Models\Plant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use function Pest\Laravel\actingAs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('user can view their own sightings', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    
    $mySighting = Sighting::factory()->create(['user_id' => $user->id, 'scientific_name' => 'My Sighting', 'latitude' => 3.1, 'longitude' => 101.6]);
    $otherSighting = Sighting::factory()->create(['user_id' => $otherUser->id, 'scientific_name' => 'Other Sighting', 'latitude' => 3.1, 'longitude' => 101.6]);

    actingAs($user)
        ->get(route('sightings.index'))
        ->assertStatus(200)
        ->assertSee('My Sighting')
        ->assertDontSee('Other Sighting');
});

test('user can view public map with all sightings', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    
    Sighting::factory()->create(['user_id' => $user->id, 'scientific_name' => 'Sighting 1', 'latitude' => 3.1, 'longitude' => 101.6]);
    Sighting::factory()->create(['user_id' => $otherUser->id, 'scientific_name' => 'Sighting 2', 'latitude' => 3.1, 'longitude' => 101.6]);

    actingAs($user)
        ->get(route('sightings.map'))
        ->assertStatus(200)
        ->assertSee('Sighting 1')
        ->assertSee('Sighting 2');
});

test('user can delete their own sighting', function () {
    Storage::fake('public');
    $user = User::factory()->create();
    $sighting = Sighting::factory()->create(['user_id' => $user->id]);

    actingAs($user)
        ->delete(route('sightings.destroy', $sighting))
        ->assertRedirect(route('sightings.index'));

    $this->assertDatabaseMissing('sightings', ['id' => $sighting->id]);
});

test('user cannot delete others sightings', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $sighting = Sighting::factory()->create(['user_id' => $otherUser->id]);

    actingAs($user)
        ->delete(route('sightings.destroy', $sighting))
        ->assertStatus(403);
});

test('user can filter public map by search', function () {
    $user = User::factory()->create();
    Sighting::factory()->create(['scientific_name' => 'Hibiscus', 'latitude' => 3.1, 'longitude' => 101.6]);
    Sighting::factory()->create(['scientific_name' => 'Rose', 'latitude' => 3.1, 'longitude' => 101.6]);

    actingAs($user)
        ->get(route('sightings.map', ['search' => 'Hibiscus']))
        ->assertStatus(200)
        ->assertSee('Hibiscus')
        ->assertDontSee('Rose');
});
