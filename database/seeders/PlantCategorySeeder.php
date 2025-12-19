<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PlantCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['category_name' => 'Trees', 'description' => 'Large woody plants, including dipterocarps.'],
            ['category_name' => 'Orchids', 'description' => 'Diverse and widespread family of flowering plants.'],
            ['category_name' => 'Ferns', 'description' => 'Non-flowering vascular plants.'],
            ['category_name' => 'Carnivorous Plants', 'description' => 'Plants that derive some or most of their nutrients from trapping and consuming animals.'],
            ['category_name' => 'Medicinal Plants', 'description' => 'Plants used for traditional medicine (Herba).'],
            ['category_name' => 'Mangroves', 'description' => 'Salt-tolerant trees and shrubs that grow in coastal intertidal zones.'],
        ];

        foreach ($categories as $category) {
            \App\Models\PlantCategory::updateOrCreate(
                ['category_name' => $category['category_name']],
                $category
            );
        }
    }
}
