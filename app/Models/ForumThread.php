<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForumThread extends Model
{
    /** @use HasFactory<\Database\Factories\ForumThreadFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'category',
        'content',
        'image',
    ];

    public function posts()
    {
        return $this->hasMany(ForumPost::class)->whereNull('parent_post_id')->orderBy('created_at', 'asc');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function tags()
    {
        return $this->belongsToMany(ForumTag::class, 'thread_tag');
    }
}
