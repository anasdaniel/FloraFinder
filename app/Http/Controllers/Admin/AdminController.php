<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ForumPost;
use App\Models\Plant;
use App\Models\Sighting;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'totalUsers' => User::count(),
                'totalPlants' => Plant::count(),
                'totalSightings' => Sighting::count(),
                'totalForumPosts' => ForumPost::count(),
            ],
            'recentSightings' => Sighting::with(['user', 'plant'])
                ->latest()
                ->take(5)
                ->get(),
            'recentUsers' => User::latest()
                ->take(5)
                ->get(),
        ]);
    }

    public function users()
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::latest()->paginate(10),
        ]);
    }

    public function toggleAdmin(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot change your own admin status.');
        }

        $user->update([
            'is_admin' => !$user->is_admin,
        ]);

        return back()->with('success', 'User admin status updated.');
    }

    public function deleteUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete yourself.');
        }

        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }
}
