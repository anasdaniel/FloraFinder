<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sighting extends Model
{
    /** @use HasFactory<\Database\Factories\SightingFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plant_id',
        'zone_id',
        'scientific_name',
        'common_name',
        'latitude',
        'longitude',
        'location_name',
        'region',
        'sighted_at',
        'image_url',
        'description',
    ];

    protected $casts = [
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'sighted_at' => 'datetime',
    ];

    /**
     * Get the user who reported this sighting.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the plant associated with this sighting (if linked).
     */
    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }

    /**
     * Get the zone where this sighting was made (if determined).
     */
    public function zone()
    {
        return $this->belongsTo(Zone::class);
    }

    /**
     * Get all images associated with this sighting.
     */
    public function images()
    {
        return $this->hasMany(SightingImage::class);
    }
}
