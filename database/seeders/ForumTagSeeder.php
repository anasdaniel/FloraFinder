<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ForumTagSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tags = [

            // 🌱 Plant Identification
            ['tag_name' => 'Plant ID Request',      'tag_category' => 'Identification'],
            ['tag_name' => 'Leaf Identification',    'tag_category' => 'Identification'],
            ['tag_name' => 'Flower Identification',  'tag_category' => 'Identification'],
            ['tag_name' => 'Fruit Identification',   'tag_category' => 'Identification'],
            ['tag_name' => 'Tree Identification',    'tag_category' => 'Identification'],
            ['tag_name' => 'Ornamental Plants',      'tag_category' => 'Identification'],
            ['tag_name' => 'Medicinal Plants',       'tag_category' => 'Identification'],

            // 🌿 Plant Care & Gardening
            ['tag_name' => 'Watering Tips',          'tag_category' => 'Care'],
            ['tag_name' => 'Soil Requirements',      'tag_category' => 'Care'],
            ['tag_name' => 'Fertilizers',            'tag_category' => 'Care'],
            ['tag_name' => 'Pest Control',           'tag_category' => 'Care'],
            ['tag_name' => 'Indoor Plants',          'tag_category' => 'Care'],
            ['tag_name' => 'Outdoor Plants',         'tag_category' => 'Care'],
            ['tag_name' => 'Propagation',            'tag_category' => 'Care'],
            ['tag_name' => 'Plant Diseases',         'tag_category' => 'Care'],

            // 🌳 Conservation + Biodiversity
            ['tag_name' => 'Endangered Species',     'tag_category' => 'Conservation'],
            ['tag_name' => 'Rare Plants',            'tag_category' => 'Conservation'],
            ['tag_name' => 'Protected Species',      'tag_category' => 'Conservation'],
            ['tag_name' => 'Reforestation',          'tag_category' => 'Conservation'],
            ['tag_name' => 'Invasive Species',       'tag_category' => 'Conservation'],

            // 🌏 Habitat & Ecology
            ['tag_name' => 'Rainforest Plants',      'tag_category' => 'Habitat'],
            ['tag_name' => 'Mangrove Species',       'tag_category' => 'Habitat'],
            ['tag_name' => 'Highland Plants',        'tag_category' => 'Habitat'],
            ['tag_name' => 'Wetland Plants',         'tag_category' => 'Habitat'],
            ['tag_name' => 'Urban Flora',            'tag_category' => 'Habitat'],

            // 💬 General Community Topics
            ['tag_name' => 'General Discussion',     'tag_category' => 'Community'],
            ['tag_name' => 'Gardening Tips',         'tag_category' => 'Community'],
            ['tag_name' => 'Plant Sharing',          'tag_category' => 'Community'],
            ['tag_name' => 'Events & Workshops',     'tag_category' => 'Community'],

            // 🆘 Help & Support
            ['tag_name' => 'Help Request',           'tag_category' => 'Support'],
            ['tag_name' => 'Beginner Questions',     'tag_category' => 'Support'],
            ['tag_name' => 'Troubleshooting',        'tag_category' => 'Support'],
        ];

        DB::table('forum_tags')->insertOrIgnore($tags);
    }
}