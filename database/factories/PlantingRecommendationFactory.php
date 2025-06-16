<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlantingRecommendation>
 */
class PlantingRecommendationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'soil_type' => $this->faker->randomElement(['Loamy', 'Sandy', 'Clay', 'Silty']),
            'water_per_week' => $this->faker->numberBetween(1, 10) . ' liters',
            'sunlight_per_week' => $this->faker->numberBetween(10, 40) . ' hours',
            'temperature_range' => $this->faker->numberBetween(10, 35) . '-' . $this->faker->numberBetween(36, 45) . '°C',
            'fertilizer_type' => $this->faker->word(),
            'humidity_level' => $this->faker->numberBetween(30, 90) . '%',
        ];
    }
}
