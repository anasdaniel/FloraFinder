<?php

namespace Database\Seeders;

use App\Models\PlantCategory;
use App\Models\User;
use App\Models\Plant;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            UserSeeder::class,
            ConservationStatusSeeder::class,
            PlantCategorySeeder::class,
            ZoneSeeder::class,
            MalaysianFloraSeeder::class,
            ForumTagSeeder::class,
        ]);

        // Plant::factory(100)->create();
    }
}
