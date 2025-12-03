<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plant extends Model
{
    /** @use HasFactory<\Database\Factories\PlantFactory> */
    use HasFactory;

    protected $fillable = [
        'common_name',
        'scientific_name',
        'family',
        'genus',
        'habitat',
        'lifespan',
        'gbif_id',
        'powo_id',
        'iucn_category',
        'description',
        'sowing',
        'days_to_harvest',
        'row_spacing_cm',
        'spread_cm',
        'ph_minimum',
        'ph_maximum',
        'light',
        'atmospheric_humidity',
        'growth_months',
        'bloom_months',
        'fruit_months',
        'minimum_precipitation_mm',
        'maximum_precipitation_mm',
        'minimum_temperature_celsius',
        'maximum_temperature_celsius',
        'soil_nutriments',
        'soil_salinity',
        'soil_texture',
        'soil_humidity',
        'care_cached_at',
        'planting_recommendation_id',
    ];

    protected $casts = [
        'growth_months' => 'array',
        'bloom_months' => 'array',
        'fruit_months' => 'array',
        'ph_minimum' => 'decimal:1',
        'ph_maximum' => 'decimal:1',
        'care_cached_at' => 'datetime',
    ];

    /**
     * Check if care details need to be refreshed (older than 7 days)
     */
    public function needsCareRefresh(): bool
    {
        if (!$this->care_cached_at) {
            return true;
        }
        return $this->care_cached_at->diffInDays(now()) > 7;
    }

    /**
     * Get formatted care details for frontend
     */
    public function getCareDetails(): array
    {
        return [
            'description' => $this->description,
            'sowing' => $this->sowing,
            'days_to_harvest' => $this->days_to_harvest,
            'row_spacing_cm' => $this->row_spacing_cm,
            'spread_cm' => $this->spread_cm,
            'ph_minimum' => $this->ph_minimum,
            'ph_maximum' => $this->ph_maximum,
            'light' => $this->light,
            'atmospheric_humidity' => $this->atmospheric_humidity,
            'growth_months' => $this->growth_months,
            'bloom_months' => $this->bloom_months,
            'fruit_months' => $this->fruit_months,
            'minimum_precipitation' => $this->minimum_precipitation_mm,
            'maximum_precipitation' => $this->maximum_precipitation_mm,
            'minimum_temperature_celcius' => $this->minimum_temperature_celsius,
            'maximum_temperature_celcius' => $this->maximum_temperature_celsius,
            'soil_nutriments' => $this->soil_nutriments,
            'soil_salinity' => $this->soil_salinity,
            'soil_texture' => $this->soil_texture,
            'soil_humidity' => $this->soil_humidity,
        ];
    }

    public function category()
    {
        return $this->belongsTo(PlantCategory::class);
    }

    public function conservationStatus()
    {
        return $this->belongsTo(ConservationStatus::class);
    }

    public function sightings()
    {
        return $this->hasMany(Sighting::class);
    }

    public function zones()
    {
        return $this->belongsToMany(Zone::class, 'plant_zone');
    }

    public function plantingRecommendations()
    {
        return $this->belongsTo(PlantingRecommendation::class, 'planting_recommendation_id');
    }
}
