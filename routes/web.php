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
    Route::get('forum', function () {
        return Inertia::render('ForumView');
    })->name('forum');

    // Create new forum post
    Route::get('forum/new', function () {
        return Inertia::render('ForumCreate');
    })->name('forum.create');

    Route::post('forum/new', function () {
        // Validate the request data
        $validated = request()->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category' => 'required|string',
            'image' => 'nullable|image|max:2048',
        ]);

        // Debug information
        \Log::info('Forum post submitted:', $validated);

        // TODO: Save the post to database
        // For now, we'll create a mock post and redirect back

        // Processing the image if one was uploaded
        $imagePath = null;
        if (request()->hasFile('image') && request()->file('image')->isValid()) {
            $imagePath = request()->file('image')->store('forum', 'public');
        }

        return redirect()->route('forum')->with('success', 'Post created successfully!');
    })->name('forum.store');


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
        // Normally you would fetch the post from the database
        // For now, we'll create a mock post based on the ID
        $post = [
            'id' => $id,
            'title' => 'Example Forum Post #' . $id,
            'content' => 'This is the content of post #' . $id . '. Replace this with actual data from your database.',
            'date' => date('Y-m-d'),
            'category' => 'general',
            'replies' => rand(0, 20),
            'author' => [
                'name' => 'Demo User',
                'avatar' => null
            ],
            'images' => []
        ];

        return Inertia::render('ForumPostView', [
            'post' => $post
        ]);
    })->name('forum.post');



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
