<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Zone extends Model
{
    /** @use HasFactory<\Database\Factories\ZoneFactory> */
    use HasFactory;

    protected $fillable = [
        'zone_name',
        'zone_type',
        'location_description',
    ];

    public function plants()
    {
        return $this->belongsToMany(Plant::class, 'zone_plant');
    }
    public function sightings()
    {
        return $this->hasMany(Sighting::class);
    }
}
