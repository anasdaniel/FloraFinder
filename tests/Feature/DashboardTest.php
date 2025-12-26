<?php

use App\Models\User;
use App\Models\Sighting;
use App\Models\PlantIdentification;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get('/dashboard');
    $response->assertRedirect('/login');
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('welcome-plant'));
});

test('dashboard stats are correct', function () {
    $user = User::factory()->create();
    
    // Create some sightings for the user
    Sighting::factory()->count(3)->create(['user_id' => $user->id]);
    
    // Create some identifications for the user (to test species logged)
    PlantIdentification::factory()->create([
        'user_id' => $user->id,
        'scientific_name' => 'Species 1',
        'family' => 'Family 1',
    ]);

    $this->actingAs($user)
        ->get(route('welcome-plant'))
        ->assertStatus(200)
        ->assertInertia(fn ($page) => $page
            ->where('stats.0.value', '3') // Total Sightings
            ->where('stats.1.value', '1') // Species Logged
        );
});
