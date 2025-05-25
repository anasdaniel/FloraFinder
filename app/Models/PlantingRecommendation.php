<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlantingRecommendation extends Model
{
    /** @use HasFactory<\Database\Factories\PlantingRecommendationFactory> */
    use HasFactory;

    protected $fillable = [
        'soil_type',
        'water_per_week',
        'sunlight_per_week',
        'temperature_range',
        'fertilizer_type',
        'humidity_level',
    ];

    public function plant()
    {
        return $this->hasOne(Plant::class);
    }

}
