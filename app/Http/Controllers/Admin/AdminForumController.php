<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ForumThread;
use App\Models\ForumPost;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminForumController extends Controller
{
              public function index()
              {
                            $threads = ForumThread::with(['user', 'tags'])
                                          ->withCount('allPosts')
                                          ->latest()
                                          ->paginate(10);

                            return Inertia::render('Admin/Forum/Index', [
                                          'threads' => $threads
                            ]);
              }

              public function show(ForumThread $thread)
              {
                            $thread->load(['user', 'tags', 'allPosts.user']);

                            return Inertia::render('Admin/Forum/Show', [
                                          'thread' => $thread
                            ]);
              }

              public function destroyThread(ForumThread $thread)
              {
                            $thread->delete();
                            return back()->with('success', 'Thread deleted successfully.');
              }

              public function destroyPost(ForumPost $post)
              {
                            $post->delete();
                            return back()->with('success', 'Post deleted successfully.');
              }
}
