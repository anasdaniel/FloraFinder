<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sighting extends Model
{
    /** @use HasFactory<\Database\Factories\SightingFactory> */
    use HasFactory;

    protected $fillable = [
        'image_url',
        'description',
        'created_at'
    ];

    // Define relationship to User model
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
