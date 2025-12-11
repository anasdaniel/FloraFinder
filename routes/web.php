<?php

use App\Http\Controllers\PlantIdentifierController;
use App\Http\Controllers\ForumController;
use App\Http\Controllers\PlantController;
use App\Http\Controllers\SightingController;
use App\Http\Controllers\MyPlantController;
use App\Http\Integrations\CheckStatusRequest;
use App\Http\Integrations\IdentifyPlantRequest;
use App\Http\Integrations\PlantNetConnector;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;
use App\Models\ForumThread;
use App\Models\ForumTag;
use App\Models\PlantIdentification;
use App\Http\Integrations\TrefleConnector;
use App\Http\Integrations\TrefleRequest;
use App\Http\Integrations\SearchPlantRequest;



Route::get('/', function () {


    return Inertia::render('Welcome');
})->name('home');

Route::get('/dashboard-preview', function () {
    return Inertia::render('Dashboard/Guest');
})->name('dashboard.preview');


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
    })->name('dashboard');

    //forum
    Route::get('/forum', [ForumController::class, 'index'])
        ->name('forum');

    // Create Thread
    Route::get('/forum/new', [ForumController::class, 'create'])->name('forum.create');
    Route::post('/forum', [ForumController::class, 'store'])->name('forum.store');


    //welcome plant
    Route::get('welcome-plant', function () {
        return Inertia::render('Dashboard/Index');
    })->name('welcome-plant');

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

        return Inertia::render('Forum/Post', [
            'thread' => $thread
        ]);
    })->name('forum.show');


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


    // Plant search route
    Route::get('plant-search', function () {


        //fetch plant from database
        $plants = PlantIdentification::all();
        // dd($plants);
        $plants = $plants->map(function ($plant) {
            return [
                'id' => $plant->id,
                'user_id' => $plant->user_id,
                'path' => $plant->url,
                'filename' => $plant->filename,
                'mime_type' => $plant->mime_type,
                'size' => $plant->size,
                'organ' => $plant->organ,
                'scientific_name' => $plant->scientific_name,
                'scientific_name_without_author' => $plant->scientific_name_without_author,
                'common_name' => $plant->common_name,
                'family' => $plant->family,
                'genus' => $plant->genus,
                'confidence' => $plant->confidence,
                'gbif_id' => $plant->gbif_id,
                'powo_id' => $plant->powo_id,
                'iucn_category' => $plant->iucn_category,
                'region' => $plant->region,
                'latitude' => $plant->latitude,
                'longitude' => $plant->longitude,
                'created_at' => $plant->created_at,
                'updated_at' => $plant->updated_at,
            ];
        });


        return Inertia::render(
            'Search/Index',
            props: [
                'plants' => $plants
            ]
        );
    })->name('plant-search');

    // My Plants Map - User's personal plant collection on a map
    // Component: resources/js/pages/MyPlants/Map.vue
    // Filtering: Client-side (Vue computed properties)
    Route::get('plant-map', function () {
        // Fetch actual sightings from database
        $sightings = \App\Models\Sighting::with(['images', 'user', 'plant'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get();

        $sightings = $sightings->map(function ($sighting) {
            return [
                'id' => $sighting->id,
                'user_id' => $sighting->user_id,
                'user_name' => $sighting->user?->name,
                'path' => $sighting->images->first()?->image_url ?? $sighting->image_url,
                'images' => $sighting->images->map(fn($img) => [
                    'id' => $img->id,
                    'url' => $img->image_url,
                    'organ' => $img->organ,
                ])->toArray(),
                'scientific_name' => $sighting->scientific_name,
                'scientific_name_without_author' => $sighting->scientific_name,
                'common_name' => $sighting->common_name,
                'family' => $sighting->plant?->family ?? null,
                'genus' => $sighting->plant?->genus ?? null,
                'iucn_category' => $sighting->plant?->iucn_category ?? null,
                'region' => $sighting->region,
                'location_name' => $sighting->location_name,
                'latitude' => $sighting->latitude,
                'longitude' => $sighting->longitude,
                'description' => $sighting->description,
                'sighted_at' => $sighting->sighted_at,
                'created_at' => $sighting->created_at,
                'updated_at' => $sighting->updated_at,
            ];
        });

        return Inertia::render(
            'MyPlants/Map',
            props: [
                'plants' => $sightings
            ]
        );
    })->name('plant-map');

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
Route::get('plant-identifier/identify', function () {
    return redirect()->route('plant-identifier');
});

Route::get(
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
