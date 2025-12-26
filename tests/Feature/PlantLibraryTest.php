<?php

use App\Models\User;
use App\Models\Plant;
use function Pest\Laravel\actingAs;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('user can search plants by scientific name', function () {
    $user = User::factory()->create();
    Plant::factory()->create(['scientific_name' => 'Hibiscus rosa-sinensis']);
    Plant::factory()->create(['scientific_name' => 'Adiantum capillus-veneris']);

    actingAs($user)
        ->get(route('plants.index', ['search' => 'Hibiscus']))
        ->assertStatus(200)
        ->assertSee('Hibiscus rosa-sinensis')
        ->assertDontSee('Adiantum capillus-veneris');
});

test('user can filter plants by family', function () {
    $user = User::factory()->create();
    Plant::factory()->create(['scientific_name' => 'Plant A', 'family' => 'Malvaceae']);
    Plant::factory()->create(['scientific_name' => 'Plant B', 'family' => 'Pteridaceae']);

    actingAs($user)
        ->get(route('plants.index', ['family' => 'Malvaceae']))
        ->assertStatus(200)
        ->assertSee('Plant A')
        ->assertDontSee('Plant B');
});

test('user can filter plants by endangered status', function () {
    $user = User::factory()->create();
    Plant::factory()->create(['scientific_name' => 'Common Plant', 'iucn_category' => 'LC']);
    Plant::factory()->create(['scientific_name' => 'Endangered Plant', 'iucn_category' => 'EN']);

    actingAs($user)
        ->get(route('plants.index', ['is_endangered' => 'true']))
        ->assertStatus(200)
        ->assertSee('Endangered Plant')
        ->assertDontSee('Common Plant');
});

test('user can view plant details', function () {
    $user = User::factory()->create();
    $plant = Plant::factory()->create([
        'scientific_name' => 'Hibiscus rosa-sinensis',
        'common_name' => 'Hibiscus',
    ]);

    actingAs($user)
        ->get(route('plants.show', $plant))
        ->assertStatus(200)
        ->assertSee('Hibiscus rosa-sinensis');
});
