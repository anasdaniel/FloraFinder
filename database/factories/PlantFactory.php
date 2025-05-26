<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plant>
 */
class PlantFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'plant_category_id' => \App\Models\PlantCategory::factory(),
            'conservation_status_id' => \App\Models\ConservationStatus::factory(),
            'planting_recommendation_id' => \App\Models\PlantingRecommendation::factory(),
            'common_name' => $this->faker->word(),
            'scientific_name' => $this->faker->word(),
            'family' => $this->faker->word(),
            'habitat' => $this->faker->word(),
            'lifespan' => $this->faker->numberBetween(1, 100),
        ];
    }
}
