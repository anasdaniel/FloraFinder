<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlantIdentification extends Model
{
    protected $fillable = [
        'path',
        'url',
        'filename',
        'mime_type',
        'size',
        'organ',
        'organ_score',
        'scientific_name',
        'scientific_name_without_author',
        'common_name',
        'family',
        'genus',
        'confidence',
        'gbif_id',
        'powo_id',
        'iucn_category',
        'region',
        'latitude',
        'longitude',
        'user_id',
    ];

    // Define relationship to User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
