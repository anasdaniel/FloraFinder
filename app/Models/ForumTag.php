<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForumTag extends Model
{
    /** @use HasFactory<\Database\Factories\ForumTagFactory> */
    use HasFactory;

    protected $fillable = [
        'tag_name',
    ];

    public function threads()
    {
        return $this->belongsToMany(ForumThread::class, 'thread_tag');
    }
}
