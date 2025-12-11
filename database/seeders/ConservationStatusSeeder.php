<?php

namespace Database\Seeders;

use App\Models\ConservationStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ConservationStatusSeeder extends Seeder
{

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Use insertOrIgnore to avoid unique constraint errors when seeding multiple times
        DB::table('conservation_statuses')->insertOrIgnore([
            [
                'status_name' => 'Least Concern',
                'description' => 'Species is widespread and abundant.'
            ],
            [
                'status_name' => 'Near Threatened',
                'description' => 'Species is close to qualifying for a threatened category in the near future.'
            ],
            [
                'status_name' => 'Vulnerable',
                'description' => 'Species is facing a high risk of extinction in the wild.'
            ],
            [
                'status_name' => 'Endangered',
                'description' => 'Species is facing a very high risk of extinction in the wild.'
            ],
            [
                'status_name' => 'Critically Endangered',
                'description' => 'Species is facing an extremely high risk of extinction in the wild.'
            ],
            [
                'status_name' => 'Extinct in the Wild',
                'description' => 'Species is known only to survive in captivity or as a naturalized population outside its historic range.'
            ],
            [
                'status_name' => 'Extinct',
                'description' => 'No known individuals remaining.'
            ],
        ]);
    }
}
