<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ForumThread;
use App\Models\ForumTag;
use App\Models\ForumPost;
use Inertia\Inertia;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class ForumController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        $threads = ForumThread::with([
            'user',
            'tags',
            'posts' => function ($query) {
                $query->whereNull('parent_post_id')->orderBy('created_at', 'asc');
            },
            'posts.user',
            'posts.replies' => function ($q) {
                $q->orderBy('created_at', 'asc');
            },
            'posts.replies.user',
        ])->withCount('allPosts as posts_count')->latest()->get();

        $allTags = ForumTag::all();

        return Inertia::render('Forum/Index', [
            'threads' => $threads,
            'allTags' => $allTags,
        ]);
    }

    public function create()
    {
        return Inertia::render('Forum/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|string',
            'image' => 'nullable|image|max:2048',
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:forum_tags,id',
        ]);

        // Handle image upload
        $imagePath = null;
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $imagePath = $request->file('image')->store('forum', 'public');
        }

        // Create the thread
        $thread = ForumThread::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'category' => $validated['category'],
            'image' => $imagePath,
            'user_id' => auth()->id(),
        ]);

        // Attach selected tags
        if (!empty($validated['tag_ids'])) {
            $thread->tags()->attach($validated['tag_ids']);
        }

        return redirect()->route('forum')->with('success', 'Thread created successfully!');
    }


    public function destroy(ForumThread $thread)
    {
        $this->authorize('delete', $thread);

        // delete all posts belonging to this thread
        $thread->posts()->delete();

        // delete the thread
        $thread->delete();

        return redirect()->back()->with('success', 'Thread deleted successfully.');
    }

    public function storeComment(Request $request, $threadId)
    {
        $this->authorize('create', ForumPost::class);

        $request->validate([
            'content' => 'required|string|max:2000',
        ]);

        $post = ForumPost::create([
            'forum_thread_id' => $threadId,
            'user_id' => auth()->id(),
            'content' => $request->content,
            'parent_post_id' => null,
        ]);

        $post->load('user');

        return response()->json(['post' => $post], 201);
    }

    public function deleteComment(ForumPost $post)
    {
        $this->authorize('delete', $post);

        $post->delete();

        return response()->json(['success' => true]);
    }

    public function show($id)
    {
        $thread = ForumThread::with(['posts.user'])->findOrFail($id);

        return inertia('Forum/Post', [
            'thread' => $thread,
        ]);
    }


    public function storeReply(Request $request, $threadId, $commentId)
    {
        $this->authorize('create', ForumPost::class);

        $request->validate(['content' => 'required']);

        // Ensure comment exists (parent)
        $comment = ForumPost::findOrFail($commentId);

        $post = ForumPost::create([
            'forum_thread_id' => $threadId,
            'user_id' => auth()->id(),
            'content' => $request->content,
            'parent_post_id' => $comment->id,
        ]);

        // load user relation for frontend display
        $post->load('user');

        return response()->json(['post' => $post], 201);
    }

    // New: return thread's top-level posts (with user + replies) as JSON for dynamic loading
    public function posts($id)
    {
        $thread = ForumThread::with([
            'posts' => function ($q) {
                $q->whereNull('parent_post_id')->orderBy('created_at', 'asc');
            },
            'posts.user',
            'posts.replies' => function ($q) {
                $q->orderBy('created_at', 'asc');
            },
            'posts.replies.user',
        ])->findOrFail($id);

        return response()->json(['posts' => $thread->posts]);
    }

    public function addTag(Request $request, ForumThread $thread)
    {
        // Only the owner can modify tags
        if ($thread->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $request->validate([
            'tag_id' => 'required|exists:forum_tags,id'
        ]);

        // Avoid duplicate tag
        if (!$thread->tags()->where('forum_tag_id', $request->tag_id)->exists()) {
            $thread->tags()->attach($request->tag_id);
        }

        return response()->json(['success' => true]);
    }

    public function removeTag(ForumThread $thread, ForumTag $forumTag)
    {
        if ($thread->user_id !== auth()->id()) {
            abort(403, 'Unauthorized');
        }

        $thread->tags()->detach($forumTag->id);

        return response()->json(['success' => true]);
    }
}
