<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlantIdentification>
 */
class PlantIdentificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => \App\Models\User::factory(),
            'path' => 'plant-identifications/' . $this->faker->uuid() . '.jpg',
            'url' => '/storage/plant-identifications/fake.jpg',
            'filename' => 'fake.jpg',
            'mime_type' => 'image/jpeg',
            'size' => 1024,
            'organ' => 'leaf',
            'scientific_name' => $this->faker->words(2, true),
            'scientific_name_without_author' => $this->faker->words(2, true),
            'family' => $this->faker->word(),
            'genus' => $this->faker->word(),
            'confidence' => $this->faker->randomFloat(2, 0, 1),
        ];
    }
}
