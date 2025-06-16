<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ConservationStatus>
 */
class ConservationStatusFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'status_name' => $this->faker->randomElement(['Endangered', 'Vulnerable', 'Least Concern', 'Extinct']),
            'description' => $this->faker->sentence(),
        ];
    }
}
