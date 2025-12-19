<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SeasonalAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'plant_id',
        'title',
        'description',
        'type',
        'state',
        'place_id',
        'source',
        'observation_count',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function plant()
    {
        return $this->belongsTo(Plant::class);
    }
}
