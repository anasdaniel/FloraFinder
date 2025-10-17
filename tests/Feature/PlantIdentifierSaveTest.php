<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('saves user_id when saving plant identification', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    $image = UploadedFile::fake()->image('plant.jpg');

    $response = $this->post(route('plant-identifier.save'), [
        'image' => $image,
        'organ' => 'leaf',
        'saveToDatabase' => true,
        'scientificName' => 'Foo bar',
        'scientificNameWithoutAuthor' => 'Foo bar',
        'commonName' => 'Foo',
        'family' => 'Fam',
        'genus' => 'Gen',
        'confidence' => 0.8,
        // Provide non-null values for non-nullable columns in migration
        'gbifId' => '123',
        'powoId' => '456',
        'iucnCategory' => 'Least Concern',
        'locationName' => 'Park',
        'region' => 'Peninsular Malaysia',
        'latitude' => 4.500000,
        'longitude' => 101.900000,
    ]);

    $response->assertRedirect(route('plant-identifier'));

    $this->assertDatabaseHas('plant_identifications', [
        'user_id' => $user->id,
        'scientific_name' => 'Foo bar',
        'genus' => 'Gen',
    ]);
});

