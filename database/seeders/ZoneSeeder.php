<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ZoneSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $zones = [
            ['zone_name' => 'Johor', 'zone_type' => 'State', 'location_description' => 'Southern Peninsular Malaysia'],
            ['zone_name' => 'Kedah', 'zone_type' => 'State', 'location_description' => 'Northern Peninsular Malaysia'],
            ['zone_name' => 'Kelantan', 'zone_type' => 'State', 'location_description' => 'Northeast Peninsular Malaysia'],
            ['zone_name' => 'Melaka', 'zone_type' => 'State', 'location_description' => 'Southwest Peninsular Malaysia'],
            ['zone_name' => 'Negeri Sembilan', 'zone_type' => 'State', 'location_description' => 'Central Peninsular Malaysia'],
            ['zone_name' => 'Pahang', 'zone_type' => 'State', 'location_description' => 'East Coast Peninsular Malaysia'],
            ['zone_name' => 'Perak', 'zone_type' => 'State', 'location_description' => 'West Coast Peninsular Malaysia'],
            ['zone_name' => 'Perlis', 'zone_type' => 'State', 'location_description' => 'Northernmost Peninsular Malaysia'],
            ['zone_name' => 'Pulau Pinang', 'zone_type' => 'State', 'location_description' => 'Northwest Peninsular Malaysia'],
            ['zone_name' => 'Sabah', 'zone_type' => 'State', 'location_description' => 'East Malaysia (Borneo)'],
            ['zone_name' => 'Sarawak', 'zone_type' => 'State', 'location_description' => 'East Malaysia (Borneo)'],
            ['zone_name' => 'Selangor', 'zone_type' => 'State', 'location_description' => 'Central Peninsular Malaysia'],
            ['zone_name' => 'Terengganu', 'zone_type' => 'State', 'location_description' => 'East Coast Peninsular Malaysia'],
            ['zone_name' => 'Kuala Lumpur', 'zone_type' => 'Federal Territory', 'location_description' => 'Capital city'],
            ['zone_name' => 'Labuan', 'zone_type' => 'Federal Territory', 'location_description' => 'Offshore island in East Malaysia'],
            ['zone_name' => 'Putrajaya', 'zone_type' => 'Federal Territory', 'location_description' => 'Administrative capital'],
        ];

        foreach ($zones as $zone) {
            \App\Models\Zone::updateOrCreate(
                ['zone_name' => $zone['zone_name']],
                $zone
            );
        }
    }
}
