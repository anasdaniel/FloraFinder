<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sighting>
 */
class SightingFactory extends Factory
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
            'plant_id' => \App\Models\Plant::factory(),
            'scientific_name' => $this->faker->words(2, true),
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            'image_url' => $this->faker->imageUrl(),
            'sighted_at' => now(),
        ];
    }
}
