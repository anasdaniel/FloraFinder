<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ForumPost extends Model
{
    /** @use HasFactory<\Database\Factories\ForumPostFactory> */
    use HasFactory;
    protected $fillable = [
        'forum_thread_id','user_id','content','image','parent_post_id',
    ];

    public $timestamps = true;

    public function thread()
    {
        return $this->belongsTo(ForumThread::class, 'forum_thread_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function replies(){
        return $this->hasMany(ForumPost::class, 'parent_post_id')->orderBy('created_at', 'asc');
    }

    public function parent()
    {
        return $this->belongsTo(ForumPost::class, 'parent_post_id');
    }
}
