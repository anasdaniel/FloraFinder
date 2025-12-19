<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlantIdentifierController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\PlantController;
use App\Http\Controllers\SightingController;
use App\Http\Controllers\MyPlantController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Models\ForumTag;



Route::get('/', [DashboardController::class, 'welcome'])->name('home');


//tags of forum
Route::get('/forum/tags', [ForumController::class, 'tags']);

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'dashboard'])->name('dashboard');

    //forum
    Route::get('/forum', [ForumController::class, 'index'])
        ->name('forum');

    // Create Thread
    Route::get('/forum/new', [ForumController::class, 'create'])->name('forum.create');
    Route::post('/forum', [ForumController::class, 'store'])->name('forum.store');


    //welcome plant
    Route::get('welcome-plant', [DashboardController::class, 'index'])->name('welcome-plant');

    //view a post
    Route::get('/forum/{id}', [ForumController::class, 'show'])->name('forum.show');


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
    Route::post('/forum/{thread}/reply/{post}', [ForumController::class, 'storeReply'])->name('forum.reply')->middleware('auth');

    // Add tag to thread
    Route::post('/forum/{thread}/tags', [ForumController::class, 'addTag'])
        ->name('forum.tags.add');

    // Remove tag from thread
    Route::delete('/forum/{thread}/tags/{forumTag}', [ForumController::class, 'removeTag'])
        ->name('forum.tags.remove');

    // Like/Unlike thread
    Route::post('/forum/{thread}/like', [ForumController::class, 'toggleLike'])
        ->name('forum.like');

    // Share thread (increment counter)
    Route::post('/forum/{thread}/share', [ForumController::class, 'incrementShare'])
        ->name('forum.share');


    // Plant search route
    Route::get('plant-search', [PlantIdentifierController::class, 'search'])->name('plant-search');

    // My Plants Map - User's personal plant collection on a map
    // Component: resources/js/pages/MyPlants/Map.vue
    Route::get('plant-map', [MyPlantController::class, 'map'])->name('plant-map');

    // Public Sightings Map - Shows all users' plant sightings
    // Component: resources/js/pages/Sightings/PublicMap.vue
    // Controller: SightingController@publicMap
    // Filtering: Server-side (Laravel with pagination)
    Route::get('sightings-map', [SightingController::class, 'publicMap'])->name('sightings.map');

    // Sighting routes
    Route::post('/sightings', [SightingController::class, 'store'])->name('sightings.store');
    Route::get('/sightings', [SightingController::class, 'index'])->name('sightings.index');
    Route::get('/sightings/{sighting}', [SightingController::class, 'show'])->name('sightings.show');
    Route::delete('/sightings/{sighting}', [SightingController::class, 'destroy'])->name('sightings.destroy');

    // My Plants routes (user's plant collection)
    Route::get('/my-plants', [MyPlantController::class, 'index'])->name('my-plants.index');
    Route::get('/my-plants/{plant}', [MyPlantController::class, 'show'])->name('my-plants.show');
    Route::delete('/my-plants/{plant}', [MyPlantController::class, 'destroy'])->name('my-plants.destroy');

    // Plants database routes
    Route::get('/plants', [PlantController::class, 'index'])->name('plants.index');
    Route::get('/plants/{plant}', [PlantController::class, 'show'])->name('plants.show');
    Route::post('/plants/{plant}/refresh-care', [PlantController::class, 'refreshCare'])->name('plants.refresh-care');

    Route::get(
        '/plant-identifier',
        [PlantIdentifierController::class, 'index']
    )->name('plant-identifier');

    Route::post(
        '/plant-identifier/save',
        [PlantIdentifierController::class, 'save']
    )->name('plant-identifier.save');
});

Route::post('plant-identifier/identify', [PlantIdentifierController::class, 'identify'])->name('plant-identifier.identify');

// Redirect GET requests to identify endpoint back to the main page
Route::get('plant-identifier/identify', [PlantIdentifierController::class, 'identifyRedirect']);

Route::post(
    '/plant-identifier/care-details',
    [PlantIdentifierController::class, 'getCareDetails']
)->name('plant-identifier.care-details');

Route::get(
    '/plant-identifier/threat-status',
    [PlantIdentifierController::class, 'getThreatStatus']
)->name('plant-identifier.threat-status');

Route::get('/plant-identifier/description', [PlantIdentifierController::class, 'generateDescription'])->name('plant-identifier.description');
Route::post('/plant-identifier/chat', [PlantIdentifierController::class, 'botanistChat'])->name('plant-identifier.chat');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
