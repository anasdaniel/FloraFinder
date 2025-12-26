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
        'likes_count',
        'shares_count',
    ];

    protected $appends = ['is_liked_by_user'];

    public function posts()
    {
        return $this->hasMany(ForumPost::class)->whereNull('parent_post_id')->orderBy('created_at', 'asc');
    }

    public function allPosts()
    {
        return $this->hasMany(ForumPost::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function tags()
    {
        return $this->belongsToMany(ForumTag::class, 'thread_tag');
    }

    public function likes()
    {
        return $this->belongsToMany(User::class, 'thread_likes')->withTimestamps();
    }

    public function getIsLikedByUserAttribute()
    {
        if (array_key_exists('is_liked_by_user', $this->attributes)) {
            return (bool) $this->attributes['is_liked_by_user'];
        }

        if (!auth()->check()) {
            return false;
        }
        return $this->likes()->where('user_id', auth()->id())->exists();
    }
}
