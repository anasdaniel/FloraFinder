<?php

use App\Http\Controllers\PlantIdentifierController;
use App\Http\Controllers\ForumController;
use App\Http\Integrations\CheckStatusRequest;
use App\Http\Integrations\IdentifyPlantRequest;
use App\Http\Integrations\PlantNetConnector;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;
use App\Http\Integrations\TrefleConnector;
use App\Http\Integrations\SearchPlantRequest;
use App\Http\Integrations\TrefleRequest;
use App\Models\PlantIdentification;

Route::get('/', function () {



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
    })->name('dashboard');


    Route::get('/map-test', function () {
        return Inertia::render('MapTest');
    })->name('map-test');


    Route::get('booking', function () {
        return Inertia::render('Booking');
    })->name('booking');



    Route::get('trefle-test', function () {
        $plantName = 'rose';

        $cacheKey = 'trefle_test_' . $plantName;
        $cachedPlants = Cache::get($cacheKey);

        if ($cachedPlants) {
            return Inertia::render('TrefleTest', [
                'plants' => $cachedPlants
            ]);
        }

        $connector = new TrefleConnector();
        $request = new SearchPlantRequest($plantName);
        $response = $connector->send($request);
        $data = $response->json();

        $plants = $data['data'];

        // Cache for 1 hour
        Cache::put($cacheKey, $plants, 3600);

        return Inertia::render('TrefleTest', [
            'plants' => $plants
        ]);
    })->name('trefle-test');

    Route::get('trefle-species', function () {

        $connector = new TrefleConnector();
        $request = new TrefleRequest(
            endpoint: 'plants/search',
            queryParameters: ['q' => 'mango', 'limit' => 10]
        );
        $response = $connector->send($request);
        $data = $response->json();
        dd($data['data']);
    });

    Route::get('trefle-malaysia', function () {
        $page = (int) request('page', 1);
        $targetCount = 20; // Desired number of unique plants per page
        $allPlants = [];
        $seenIds = [];
        $currentPage = $page;
        $pagination = [];

        $connector = new TrefleConnector();

        // Loop to fetch pages until we have enough unique plants
        while (count($allPlants) < $targetCount) {
            $request = new TrefleRequest(
                endpoint: 'distributions/42/species',
                queryParameters: ['page' => $currentPage]
            );
            $response = $connector->send($request);
            $data = $response->json();

            $plants = $data['data'] ?? [];

            // Filter out duplicates within this page and across accumulated plants
            $uniquePlants = array_filter($plants, function ($plant) use (&$seenIds) {
                if (in_array($plant['id'], $seenIds)) {
                    return false;
                }
                $seenIds[] = $plant['id'];
                return true;
            });

            // Add unique plants to the collection
            $allPlants = array_merge($allPlants, $uniquePlants);

            // Extract pagination links from the current response
            if (isset($data['links'])) {
                $pagination = [];
                foreach ($data['links'] as $key => $link) {
                    $pagination[$key] = str_replace('/api/v1/', '/', $link);
                }
            }

            // Check if there's a next page; if not, stop
            if (!isset($data['links']['next'])) {
                break;
            }

            $currentPage++; // Move to next page for next iteration
        }

        // Trim to exactly 30 plants if we have more
        $plants = array_slice($allPlants, 0, $targetCount);

        return Inertia::render('TrefleMalaysia', [
            'plants' => $plants,
            'pagination' => $pagination,
            'currentPage' => $page,
        ]);
    })->name('trefle-malaysia');


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
            'PlantSearch',
            props: [
                'plants' => $plants
            ]
        );
    })->name('plant-search');

    // Plant Map route
    Route::get('plant-map', function () {


        //fetch plant from database
        $plants = PlantIdentification::all();

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

        // dd($plants);


        return Inertia::render(
            'PlantMap',
            props: [
                'plants' => $plants
            ]
        );
    })->name('plant-map');

    Route::get('sighting-map', function () {
        return Inertia::render(component: 'SightingMap');
    })->name('sighting-map');


    //detect route
    // Route::get('detect', function () {
    //     return Inertia::render('Detect');
    // })->name('detect');

    //test dd env variable
    Route::get('test-env', function () {
        dd(env('PLANTNET_API_KEY'));
    })->name('test-env');



});

Route::get(
    '/plant-identifier',
    [PlantIdentifierController::class, 'index']
)->name('plant-identifier');
Route::post(
    '/plant-identifier/identify',
    [PlantIdentifierController::class, 'identify']
)->name('plant-identifier.identify');
Route::post(
    '/plant-identifier/save',
    [PlantIdentifierController::class, 'save']
)->name('plant-identifier.save');


Route::get(
    '/plant-identifier/care-details',
    [PlantIdentifierController::class, 'getCareDetails']
)->name('plant-identifier.care-details');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
