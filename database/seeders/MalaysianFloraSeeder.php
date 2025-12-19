<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class MalaysianFloraSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $trees = \App\Models\PlantCategory::where('category_name', 'Trees')->first();
        $orchids = \App\Models\PlantCategory::where('category_name', 'Orchids')->first();
        $carnivorous = \App\Models\PlantCategory::where('category_name', 'Carnivorous Plants')->first();
        $medicinal = \App\Models\PlantCategory::where('category_name', 'Medicinal Plants')->first();

        $lc = \App\Models\ConservationStatus::where('status_name', 'Least Concern')->first();
        $vu = \App\Models\ConservationStatus::where('status_name', 'Vulnerable')->first();
        $en = \App\Models\ConservationStatus::where('status_name', 'Endangered')->first();
        $cr = \App\Models\ConservationStatus::where('status_name', 'Critically Endangered')->first();

        $plants = [
            [
                'common_name' => 'Hibiscus',
                'malay_name' => 'Bunga Raya',
                'scientific_name' => 'Hibiscus rosa-sinensis',
                'family' => 'Malvaceae',
                'habitat' => 'Tropical',
                'lifespan' => 'Perennial',
                'is_endemic' => false,
                'is_native' => true,
                'plant_category_id' => $trees?->id,
                'conservation_status_id' => $lc?->id,
                'description' => 'The national flower of Malaysia, known for its vibrant red petals.',
            ],
            [
                'common_name' => 'Rafflesia',
                'malay_name' => 'Bunga Pakma',
                'scientific_name' => 'Rafflesia arnoldii',
                'family' => 'Rafflesiaceae',
                'habitat' => 'Rainforest',
                'lifespan' => 'Short-lived bloom',
                'is_endemic' => true,
                'is_native' => true,
                'plant_category_id' => $trees?->id, // It's a parasite but let's put it here for now
                'conservation_status_id' => $cr?->id,
                'description' => 'The largest individual flower in the world, found in the rainforests of Sabah and Sarawak.',
            ],
            [
                'common_name' => 'King Pitcher Plant',
                'malay_name' => 'Periuk Kera',
                'scientific_name' => 'Nepenthes rajah',
                'family' => 'Nepenthaceae',
                'habitat' => 'Montane forest',
                'lifespan' => 'Perennial',
                'is_endemic' => true,
                'is_native' => true,
                'plant_category_id' => $carnivorous?->id,
                'conservation_status_id' => $en?->id,
                'description' => 'An endemic pitcher plant found only on Mount Kinabalu and Mount Tambuyukon in Sabah.',
            ],
            [
                'common_name' => 'Tongkat Ali',
                'malay_name' => 'Tongkat Ali',
                'scientific_name' => 'Eurycoma longifolia',
                'family' => 'Simaroubaceae',
                'habitat' => 'Lowland forest',
                'lifespan' => 'Perennial',
                'is_endemic' => false,
                'is_native' => true,
                'plant_category_id' => $medicinal?->id,
                'conservation_status_id' => $lc?->id,
                'description' => 'A popular medicinal plant in Malaysia known for its health benefits.',
            ],
        ];

        foreach ($plants as $plantData) {
            \App\Models\Plant::updateOrCreate(
                ['scientific_name' => $plantData['scientific_name']],
                $plantData
            );
        }
    }
}
