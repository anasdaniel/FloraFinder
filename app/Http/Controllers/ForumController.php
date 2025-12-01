<?php

namespace App\Http\Controllers;

use App\Models\ForumThread;
use App\Models\ForumPost;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

class ForumController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->input('category');
        
        $threads = ForumThread::with('user')
            ->withCount('posts')
            ->when($category && $category !== 'general', function ($query) use ($category) {
                return $query->where('category', $category);
            })
            ->latest()
            ->get()
            ->map(function ($thread) {
                return [
                    'id' => $thread->id,
                    'title' => $thread->title,
                    'excerpt' => \Illuminate\Support\Str::limit($thread->content, 100),
                    'category' => $thread->category,
                    'date' => $thread->created_at,
                    'replies' => $thread->posts_count,
                    'author' => [
                        'id' => $thread->user->id,
                        'name' => $thread->user->name,
                        'avatar' => $thread->user->profile_photo_url ?? null,
                    ],
                ];
            });

        return Inertia::render('ForumView', [
            'threads' => $threads,
            'currentCategory' => $category ?? 'general',
        ]);
    }

    public function create()
    {
        return Inertia::render('ForumCreate');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|string',
            'image' => 'nullable|image|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('forum-images', 'public');
        }

        ForumThread::create([
            'user_id' => Auth::id(),
            'title' => $validated['title'],
            'content' => $validated['content'],
            'category' => $validated['category'],
            'image' => $imagePath,
        ]);

        return redirect()->route('forum')->with('success', 'Thread created successfully!');
    }

    public function show($id)
    {
        $thread = ForumThread::with(['user', 'posts.user', 'posts.parent.user'])->findOrFail($id);

        return Inertia::render('ForumPostView', [
            'post' => [
                'id' => $thread->id,
                'title' => $thread->title,
                'content' => $thread->content,
                'category' => $thread->category,
                'date' => $thread->created_at,
                'replies' => $thread->posts->count(),
                'author' => [
                    'name' => $thread->user->name,
                    'avatar' => $thread->user->profile_photo_url ?? null,
                ],
                'images' => $thread->image ? [asset('storage/' . $thread->image)] : [],
                'posts' => $thread->posts->map(function ($post) {
                    return [
                        'id' => $post->id,
                        'content' => $post->content,
                        'date' => $post->created_at,
                        'author' => [
                            'name' => $post->user->name,
                            'avatar' => $post->user->profile_photo_url ?? null,
                        ],
                        'parent' => $post->parent ? [
                            'id' => $post->parent->id,
                            'content' => \Illuminate\Support\Str::limit($post->parent->content, 50),
                            'author' => [
                                'name' => $post->parent->user->name,
                            ]
                        ] : null,
                    ];
                }),
            ]
        ]);
    }
    
    public function storeReply(Request $request, $id)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'parent_id' => 'nullable|exists:forum_posts,id',
        ]);

        ForumPost::create([
            'forum_thread_id' => $id,
            'user_id' => Auth::id(),
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
        ]);

        return redirect()->back()->with('success', 'Reply added successfully!');
    }
}
