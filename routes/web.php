<?php

use App\Http\Controllers\PlantIdentifierController;
use App\Http\Controllers\ForumController;
use App\Http\Integrations\CheckStatusRequest;
use App\Http\Integrations\IdentifyPlantRequest;
use App\Http\Integrations\PlantNetConnector;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Client;
use App\Models\ForumThread;
use App\Models\ForumTag;



Route::get('/', function () {

    // return redirect('/dashboard');

    return Inertia::render('Welcome');
})->name('home');

// Map Test Route



Route::get('/login', function () {
    return Inertia::render('Auth/Login');
})->name('login');

//register
Route::get('/register', function () {
    return Inertia::render('Auth/Register');
})->name('register');

//tags of forum
Route::get('/forum/tags', function () {
    return response()->json([
        'tags' => ForumTag::all(), // get all tags
    ]);
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {

        return redirect('welcome-plant');

        return Inertia::render('Dashboard');
    })->name('dashboard');


    Route::get('/map-test', function () {
        return Inertia::render('MapTest');
    })->name('map-test');


    Route::get('booking', function () {
        return Inertia::render('Booking');
    })->name('booking');


    //forum
    Route::get('/forum', [ForumController::class, 'index'])
        ->name('forum');

    // Create new forum post
//    Route::post('forum/new', function () {
//        $validated = request()->validate([
//            'title' => 'required|string|max:255',
//            'content' => 'required|string',
//            'category' => 'required|string',
//            'image' => 'nullable|image|max:2048',
//        ]);
//
//        // Handle image upload
//        $imagePath = null;
//        if (request()->hasFile('image') && request()->file('image')->isValid()) {
//            $imagePath = request()->file('image')->store('forum', 'public');
//        }
//
//        // Create thread with content + image
//        $thread = \App\Models\ForumThread::create([
//            'title' => $validated['title'],
//            'category' => $validated['category'],
//            'content' => $validated['content'],
//            'image' => $imagePath,
//            'user_id' => auth()->id(),
//        ]);
//
//        return redirect()->route('forum')->with('success', 'Thread created successfully!');
//    })->name('forum.store');



    //welcome plant
    Route::get('welcome-plant', function () {
        return Inertia::render('WelcomePlant');
    })->name('welcome-plant');

    Route::get('event-listener', function () {

        // get current user data
        $user = auth()->user();


        //dispatch an event
        event(new App\Events\PodcastProcessed($user));
    })->name('financial');

    Route::get('/model', function () {
        return Inertia::render('Model');
    })->name('model');


    //view a post
    Route::get('/forum/{id}', function ($id) {
        $thread = ForumThread::with([
            'user',
            'tags',
            'posts' => function ($q) {
                $q->whereNull('parent_post_id')->orderBy('created_at', 'asc'); // top-level comments only
            },
            'posts.user',
            'posts.replies' => function ($q) {
                $q->orderBy('created_at', 'asc'); // replies of each post
            },
            'posts.replies.user'
        ])->findOrFail($id);

        return Inertia::render('ForumView', [
            'thread' => $thread
        ]);
    })->name('forum.show');

    // Show "Create Thread" page
    // Submit new thread
    Route::middleware(['auth'])->group(function () {
        Route::get('/forum/new', [ForumController::class, 'create'])->name('forum.create');
        Route::post('/forum', [ForumController::class, 'store'])->name('forum.store');
    });

    //delete a thread
    Route::delete('/forum/{thread}', [ForumController::class, 'destroy'])->name('forum.destroy');

    //store comment
    Route::post('/forum/{thread}/comments', [ForumController::class, 'storeComment'])
        ->middleware('auth');

    // Fetch comments (JSON) for a thread (used by frontend to refresh comments)
    Route::get('/forum/{id}/posts', [ForumController::class, 'posts'])
        ->name('forum.posts')
        ->middleware('auth');

    //delete route
    Route::delete('/forum/comment/{post}', [ForumController::class, 'deleteComment'])
        ->name('forum.comment.delete');


    //store reply
    Route::post('/forum/{thread}/reply/{post}', [ForumController::class, 'storeReply'])->name('forum.reply');

    // Add tag to thread
    Route::post('/forum/{thread}/tags', [ForumController::class, 'addTag'])
        ->name('forum.tags.add');

    // Remove tag from thread
    Route::delete('/forum/{thread}/tags/{forumTag}', [ForumController::class, 'removeTag'])
        ->name('forum.tags.remove');


    // Plant search route
    Route::get('plant-search', function () {
        return Inertia::render('PlantSearch');
    })->name('plant-search');

    // Plant Map route
    Route::get('plant-map', function () {
        return Inertia::render('PlantMap');
    })->name('plant-map');

    Route::get('sighting-map', function () {
        return Inertia::render(component: 'SightingMap');
    })->name('plant-map');

    //detect route
    // Route::get('detect', function () {
    //     return Inertia::render('Detect');
    // })->name('detect');


    Route::get(
        '/plant-identifier',
        [PlantIdentifierController::class, 'index']
    )->name('plant-identifier');
    Route::post(
        '/plant-identifier/identify',
        [PlantIdentifierController::class, 'identify']
    )->name('plant-identifier.identify');
});


require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
