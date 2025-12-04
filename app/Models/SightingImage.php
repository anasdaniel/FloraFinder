<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SightingImage extends Model
{
              use HasFactory;

              protected $fillable = [
                            'sighting_id',
                            'image_url',
                            'organ',
                            'organ_score',
              ];

              /**
               * Get the sighting that owns this image.
               */
              public function sighting()
              {
                            return $this->belongsTo(Sighting::class);
              }
}
