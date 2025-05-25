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
        'habitat',
        'lifespan',
    ];

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
        return $this-hasMany(Sighting::class);
    }
    public function zones()
    {
        return $this->belongsToMany(Zone::class, 'plant_zone');
    }
    public function plantingRecommendations()
    {
        return $this->belongsTo(Recommendation::class);
    }
}
