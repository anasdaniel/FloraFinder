<?php

namespace Tests\Unit;

use App\Models\Plant;
use App\Services\PlantCacheService;
use App\Services\CareDetailsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PlantCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_find_or_create_with_care_creates_plant_if_not_exists()
    {
        $careDetailsService = Mockery::mock(CareDetailsService::class);
        $service = new PlantCacheService($careDetailsService);

        $scientificName = 'Hibiscus rosa-sinensis';
        
        $plant = $service->findOrCreateWithCare($scientificName);

        $this->assertInstanceOf(Plant::class, $plant);
        $this->assertEquals($scientificName, $plant->scientific_name);
        $this->assertDatabaseHas('plants', ['scientific_name' => $scientificName]);
    }

    public function test_find_or_create_with_care_updates_existing_plant()
    {
        $careDetailsService = Mockery::mock(CareDetailsService::class);
        $service = new PlantCacheService($careDetailsService);

        $scientificName = 'Hibiscus rosa-sinensis';
        $plant = Plant::create(['scientific_name' => $scientificName]);

        $service->findOrCreateWithCare($scientificName, 'Hibiscus', 'Malvaceae');

        $plant->refresh();
        $this->assertEquals('Hibiscus', $plant->common_name);
        $this->assertEquals('Malvaceae', $plant->family);
    }
}
