<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForumPost extends Model
{
    /** @use HasFactory<\Database\Factories\ForumPostFactory> */
    use HasFactory;
    protected $fillable = [
        'content',
    ];

    public function thread()
    {
        return $this->belongsTo(ForumThread::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
