<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConservationStatus extends Model
{
    /** @use HasFactory<\Database\Factories\ConservationStatusFactory> */
    use HasFactory;
    
    protected $fillable = [
        'status_name',
        'description',
    ];

    public function plants()
    {
        return $this->hasMany(Plant::class);
    }
}
