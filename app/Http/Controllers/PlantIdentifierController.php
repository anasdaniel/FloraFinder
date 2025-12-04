<?php

namespace App\Http\Controllers;

use App\Http\Integrations\IdentifyPlantRequest as IntegrationsIdentifyPlantRequest;
use App\Models\PlantIdentification;
use App\Services\PlantCacheService;
use App\Services\CareDetailsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use App\Http\Integrations\PlantNetConnector as IntegrationsPlantNetConnector;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Http\Integrations\TrefleConnector;
use App\Http\Integrations\SearchPlantRequest;
use App\Http\Integrations\TrefleRequest;

class PlantIdentifierController extends Controller
{
    public function __construct(
        private PlantCacheService $plantCacheService,
        private CareDetailsService $careDetailsService
    ) {}

    public function index()
    {
        return Inertia::render('Identifier/Index');
    }

    public function identify(Request $request)
    {
        $organsInput = $request->input('organs');
        if (is_string($organsInput)) {
            $decodedOrgans = json_decode($organsInput, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $request->merge(['organs' => $decodedOrgans]);
            }
        }

        $request->validate([
            'images' => 'required|array|min:1|max:5',
            'images.*' => 'file|image|max:10240',
            'organs' => 'required|array',
            'organs.*' => 'required|string|in:flower,leaf,fruit,bark,auto',
        ]);

        if (count($request->input('organs', [])) !== count($request->file('images', []))) {
            return back()->withErrors([
                'organs' => 'Please select an organ for each uploaded image.',
            ]);
        }


        try {

            $images = $request->file('images');
            $organs = $request->input('organs');

            // Create cache key based on all images
            $cacheKeyParts = [];
            foreach ($images as $image) {
                $cacheKeyParts[] = hash_file('sha256', $image->getRealPath());
            }
            $cacheKey = 'plant_' . md5(implode('_', $cacheKeyParts));

            $plantData = null;
            $cachedResult = Cache::store('redis')->get($cacheKey);
            if ($cachedResult) {
                $plantData = $cachedResult;
            } else {
                $result = $this->processPlantIdentification($images, $organs);
                Cache::store('redis')->put($cacheKey, $result, 3600);
                $plantData = $result;
            }

            return Inertia::render('Identifier/Index', [
                'plantData' => $plantData
            ]);
        } catch (\Exception $e) {
            Log::error('Plant identification error: ' . $e->getMessage());

            return Inertia::render('Identifier/Index', [
                'plantData' => [
                    'success' => false,
                    'message' => 'An error occurred during identification',
                    'error' => $e->getMessage()
                ]
            ]);
        }
    }

    public function generateDescription(Request $request)
    {
        $request->validate([
            'scientificName' => 'required|string|max:255',
        ]);
        $scientificName = $request->input('scientificName');
        $text = $this->careDetailsService->generatePlantDescription($scientificName);
        return response()->json(['success' => true, 'description' => $text]);
    }

    public function botanistChat(Request $request)
    {
        $request->validate([
            'plantName' => 'required|string|max:255',
            'message' => 'required|string',
            'history' => 'nullable|array',
        ]);
        $plantName = $request->input('plantName');
        $message = $request->input('message');
        $history = $request->input('history', []);

        $reply = $this->careDetailsService->generateBotReply($plantName, $history, $message);
        return response()->json(['success' => true, 'reply' => $reply]);
    }

    public function save(Request $request)
    {


        $request->validate([
            'image' => 'required|image|max:10240',
            'organ' => 'required|string|in:flower,leaf,fruit,bark,auto',
            'saveToDatabase' => 'required|boolean',
            'scientificName' => 'required|string|max:255',
            'scientificNameWithoutAuthor' => 'required|string|max:255',
            'commonName' => 'nullable|string|max:255',
            'family' => 'required|string|max:255',
            'genus' => 'required|string|max:255',
            'confidence' => 'required|numeric|between:0,1',
            'gbifId' => 'nullable|string|max:255',
            'powoId' => 'nullable|string|max:255',
            'iucnCategory' => 'nullable|string|max:255',
            'locationName' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
        ]);


        try {


            // Save to database with all plant information
            $savedData = $this->saveToDatabase(
                $request->file('image'),
                $request->input('organ'),
                [
                    'scientificName' => $request->input('scientificName'),
                    'scientificNameWithoutAuthor' => $request->input('scientificNameWithoutAuthor'),
                    'commonName' => $request->input('commonName'),
                    'family' => $request->input('family'),
                    'genus' => $request->input('genus'),
                    'confidence' => $request->input('confidence'),
                    'gbifId' => $request->input('gbifId'),
                    'powoId' => $request->input('powoId'),
                    'iucnCategory' => $request->input('iucnCategory'),
                    'locationName' => $request->input('locationName'),
                    'region' => $request->input('region'),
                    'latitude' => $request->input('latitude'),
                    'longitude' => $request->input('longitude'),
                    'user_id' => auth()->id(),
                ]
            );


            Log::info('Plant identification saved to database', ['data' => $savedData]);


            return redirect()->route('plant-identifier')->with('success', 'Plant identification saved successfully!');
        } catch (\Exception $e) {
            Log::error('Plant save error: ' . $e->getMessage());

            return back()->withErrors([
                'save' => 'Failed to save plant to database: ' . $e->getMessage()
            ]);
        }
    }

    public function getCareDetails(Request $request)
    {
        $request->validate([
            'scientificName' => 'required|string|max:255',
            'commonName' => 'nullable|string|max:255',
            'family' => 'nullable|string|max:255',
        ]);

        try {
            $scientificName = $request->input('scientificName');
            $commonName = $request->input('commonName');
            $family = $request->input('family');

            // Use CareDetailsService to get care details (Trefle first, Gemini fallback)
            $result = $this->careDetailsService->getCareDetails(
                $scientificName,
                $commonName,
                $family
            );

            Log::info("Retrieved care details for {$scientificName}", [
                'source' => $result['source'] ?? 'unknown',
                'success' => $result['success'] ?? false,
            ]);

            return response()->json($result);
        } catch (\Exception $e) {
            Log::error('Care details error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'source' => 'none',
                'message' => 'Failed to fetch care details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function processPlantIdentification(array $imageFiles, array $organs)
    {
        // Save all uploaded images
        $paths = [];
        $fullPaths = [];
        foreach ($imageFiles as $imageFile) {
            $path = $imageFile->store('temp-plant-images');
            $paths[] = $path;
            $fullPaths[] = Storage::path($path);
        }

        try {
            // Get API response with all images
            $response = $this->sendIdentificationRequest($fullPaths, $organs);

            // Process response
            if ($response->successful()) {
                $json = $response->json();
                $result = [
                    'success' => true,
                    'data' => $json,
                    'predictedOrgans' => $json['predictedOrgans'] ?? [],
                ];
            } else {
                $result = [
                    'success' => false,
                    'message' => 'API returned an error: ' . $response->status(),
                    'error' => $response->json() ?? $response->body()
                ];
            }

            return $result;
        } finally {
            // Clean up all temporary files
            foreach ($paths as $path) {
                Storage::delete($path);
            }
        }
    }

    private function sendIdentificationRequest(array $imagePaths, array $organs)
    {
        $apiKey = env('PLANTNET_API_KEY');

        $connector = new IntegrationsPlantNetConnector();

        $plantNetRequest = new IntegrationsIdentifyPlantRequest(
            'all',       // project
            $imagePaths, // array of local image paths
            $apiKey,     // API key
            $organs      // array of organ types
        );

        return $connector->send($plantNetRequest);
    }

    /**
     * Save plant image and metadata to database (mock implementation)
     *
     * @param \Illuminate\Http\UploadedFile $imageFile
     * @param string $organ
     * @param array $plantData
     * @return array
     */
    private function saveToDatabase($imageFile, $organ, $plantData = [])
    {
        // Save the image to permanent storage
        $path = $imageFile->store('plant-identifications', 'public');
        $fullUrl = Storage::url($path);


        $savedData = [
            'path' => $path,
            'url' => $fullUrl,
            'filename' => $imageFile->getClientOriginalName(),
            'mime_type' => $imageFile->getMimeType(),
            'size' => $imageFile->getSize(),
            'organ' => $organ,

            // Plant identification data
            'scientific_name' => $plantData['scientificName'] ?? null,
            'scientific_name_without_author' => $plantData['scientificNameWithoutAuthor'] ?? null,
            'common_name' => $plantData['commonName'] ?? null,
            'family' => $plantData['family'] ?? null,
            'genus' => $plantData['genus'] ?? null,
            'confidence' => $plantData['confidence'] ?? null,
            'gbif_id' => $plantData['gbifId'] ?? null,
            'powo_id' => $plantData['powoId'] ?? null,
            'iucn_category' => $plantData['iucnCategory'] ?? null,

            // Location data
            'location_name' => $plantData['locationName'] ?? null,
            'region' => $plantData['region'] ?? null,
            'latitude' => $plantData['latitude'] ?? null,
            'longitude' => $plantData['longitude'] ?? null,

            'uploaded_at' => now()->toDateTimeString(),
            'user_id' => $plantData['user_id'],
        ];

        // Log the saved data for demonstration
        Log::info('Plant identification saved to database (mock)', $savedData);

        PlantIdentification::create($savedData);

        return $savedData;
    }
}
