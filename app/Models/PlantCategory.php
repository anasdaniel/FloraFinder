<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlantCategory extends Model
{
    /** @use HasFactory<\Database\Factories\PlantCategoryFactory> */
    use HasFactory;

    protected $fillable = [
        'category_name',
        'description',
    ];
    public function plants()
    {
        return $this->hasMany(Plant::class);
    }
}
