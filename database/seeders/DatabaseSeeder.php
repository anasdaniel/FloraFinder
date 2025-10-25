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

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password'=> bcrypt('password'),
            'email_verified_at' => now(),
            'remember_token' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

//        Plant::factory(100)->create();

        $conservationStatus = [
            ['Not Evaluated', 'Species that have not yet been evaluated against the criteria.'],
            ['Data Deficient', 'Species for which there is insufficient information to assess extinction risk.'],
            ['Least Concern', 'Species evaluated to be at low risk of extinction.'],
            ['Near Threatened', 'Species close to qualifying for a threatened category.'],
            ['Vulnerable', 'Species facing a high risk of endangerment in the wild.'],
            ['Endangered', 'Species facing a very high risk of extinction in the wild.'],
            ['Critically Endangered', 'Species facing an extremely high risk of extinction in the wild.'],
            ['Extinct in the Wild', 'Species known only to survive in cultivation, in captivity, or as a naturalized population outside its past range.'],
        ];

        foreach ($conservationStatus as [$name, $description]) {
            PlantCategory::create([
                'category_name' => $name,
                'description' => $description,
            ]);
        }
    }
}
