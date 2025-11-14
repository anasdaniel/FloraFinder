<?php

namespace App\Http\Controllers;

use App\Http\Integrations\IdentifyPlantRequest as IntegrationsIdentifyPlantRequest;
use App\Models\PlantIdentification;
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
    public function index()
    {

        return Inertia::render('Detect');
    }

    public function identify(Request $request)
    {
        $request->validate([
            'image' => 'required|image|max:10240', // Max 10MB
            'organ' => 'required|string|in:flower,leaf,fruit,bark,habit,other',
        ]);

        try {

            $cacheKey = 'plant_' . md5($request->file('image')->get());

            // Check cache first
            $cachedResult = Cache::store('redis')->get($cacheKey);
            if ($cachedResult) {
                return Inertia::render('Detect', [
                    'plantData' => $cachedResult
                ]);
            }

            $result = $this->processPlantIdentification(
                $request->file('image'),
                $request->input('organ')
            );

            Cache::store('redis')->put($cacheKey, $result, 3600);


            return Inertia::render('Detect', [
                'plantData' => $result
            ]);
        } catch (\Exception $e) {
            Log::error('Plant identification error: ' . $e->getMessage());

            return back()->with('data', [
                'success' => false,
                'message' => 'An error occurred during identification',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function save(Request $request)
    {


        $request->validate([
            'image' => 'required|image|max:10240',
            'organ' => 'required|string|in:flower,leaf,fruit,bark,habit,other',
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
            //grab current user id if logged in

        ]);


        try {


//            dd($request->input('latitude'), $request->input('longitude'), $request->input('family'));

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

            //return to the detect page with success message and reset form

//            dd($savedData);

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
        ]);

        try {

            $scientificName = 'Sorbus aucuparia';

            $connector = new TrefleConnector();
            $detailsRequest = new SearchPlantRequest($scientificName);
            $detailsResponse = $connector->send($detailsRequest);

            if (!$detailsResponse->successful()) {
                Log::error('Details request failed: ' . $detailsResponse->body());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch plant details from Trefle API'
                ], 500);
            }

            $detailsData = $detailsResponse->json();
            $growth = $detailsData['data']['growth'] ?? null;

            if (!$growth) {
                return response()->json([
                    'success' => false,
                    'message' => 'No plant data available'
                ], 404);
            }

            $careDetails = [
                'description' => $growth['description'] ?? 'No description available',
                'sowing' => $growth['sowing'] ?? 'No sowing information available',
                'days_to_harvest' => $growth['days_to_harvest'] ?? 'Unknown',
                'row_spacing_cm' => $growth['row_spacing']['cm'] ?? 'Unknown',
                'spread_cm' => $growth['spread']['cm'] ?? 'Unknown',
                'ph_maximum' => $growth['ph_maximum'] ?? 'Unknown',
                'ph_minimum' => $growth['ph_minimum'] ?? 'Unknown',
                'light' => $growth['light'] ?? 'Unknown',
                'atmospheric_humidity' => $growth['atmospheric_humidity'] ?? 'Unknown',
                'growth_months' => $growth['growth_months'] ?? 'Unknown',
                'bloom_months' => $growth['bloom_months'] ?? 'Unknown',
                'fruit_months' => $growth['fruit_months'] ?? 'Unknown',
                'minimum_precipitation' => $growth['minimum_precipitation']['mm'] ?? 'Unknown',
                'maximum_precipitation' => $growth['maximum_precipitation']['mm'] ?? 'Unknown',
                'minimum_temperature_celcius' => $growth['minimum_temperature']['deg_c'] ?? 'Unknown',
                'maximum_temperature_celcius' => $growth['maximum_temperature']['deg_c'] ?? 'Unknown',
                'soil_nutriments' => $growth['soil_nutriments'] ?? 'Unknown',
                'soil_salinity' => $growth['soil_salinity'] ?? 'Unknown',
                'soil_texture' => $growth['soil_texture'] ?? 'Unknown',
                'soil_humidity' => $growth['soil_humidity'] ?? 'Unknown',
            ];


            return response()->json([
                'success' => true,
                'data' => $careDetails
            ]);

        } catch (\Exception $e) {
            Log::error('Trefle API error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch care details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    private function processPlantIdentification($imageFile, $organ)
    {
        // Save the uploaded image
        $path = $imageFile->store('temp-plant-images');
        $fullPath = Storage::path($path);

        try {
            // Get API response
            $response = $this->sendIdentificationRequest($fullPath, $organ);

            // Process response
            if ($response->successful()) {
                $result = [
                    'success' => true,
                    'data' => $response->json()
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
            // Clean up the temporary file
            Storage::delete($path);
        }
    }

    private function sendIdentificationRequest($imagePath, $organ)
    {
        $apiKey = env('PLANTNET_API_KEY');


        $connector = new IntegrationsPlantNetConnector();

        $plantNetRequest = new IntegrationsIdentifyPlantRequest(
            'all',     // project
            $imagePath, // local image path
            $apiKey,   // API key
            $organ     // organ type
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
