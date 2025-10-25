<?php

namespace App\Http\Controllers;

use App\Http\Integrations\IdentifyPlantRequest as IntegrationsIdentifyPlantRequest;
use App\Models\PlantIdentification;
use Illuminate\Http\Request;
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
            $result = $this->processPlantIdentification(
                $request->file('image'),
                $request->input('organ')
            );

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
            $scientificName = $request->input('scientificName');
            Log::info('Fetching care details for: ' . $scientificName);

            // First, search for the plant
            $connector = new TrefleConnector();
            $searchRequest = new SearchPlantRequest($scientificName);
            $searchResponse = $connector->send($searchRequest);

            Log::info('Search response status: ' . $searchResponse->status());
            Log::info('Search response body: ' . $searchResponse->body());

            if ($searchResponse->successful()) {
                $searchData = $searchResponse->json();
                Log::info('Search data: ', $searchData);

                if (isset($searchData['data']) && count($searchData['data']) > 0) {
                    $plantSlug = $searchData['data'][0]['slug'];
                    Log::info('Found plant slug: ' . $plantSlug);

                    // Fetch detailed plant information
                    $detailsRequest = new TrefleRequest("plants/{$plantSlug}");
                    $detailsResponse = $connector->send($detailsRequest);

                    Log::info('Details response status: ' . $detailsResponse->status());
                    Log::info('Details response body: ' . $detailsResponse->body());

                    if ($detailsResponse->successful()) {
                        $detailsData = $detailsResponse->json();
                        Log::info('Details data: ', $detailsData);

                        // Extract care details - try different possible structures
                        $mainSpecies = $detailsData['data']['main_species'] ?? $detailsData['data'] ?? null;
                        if ($mainSpecies && isset($mainSpecies['growth'])) {
                            $growth = $mainSpecies['growth'];
                            $careDetails = [
                                'watering' => $growth['watering'] ?? $growth['water'] ?? 'Unknown',
                                'sunlight' => $growth['light'] ?? $growth['sunlight'] ?? 'Unknown',
                                'soil' => $growth['soil'] ?? 'Unknown',
                                'temperature' => $growth['temperature'] ?? $growth['temp'] ?? 'Unknown',
                            ];
                        } else {
                            // Try alternative structure
                            $careDetails = [
                                'watering' => $detailsData['data']['watering'] ?? $detailsData['watering'] ?? 'Unknown',
                                'sunlight' => $detailsData['data']['sunlight'] ?? $detailsData['sunlight'] ?? 'Unknown',
                                'soil' => $detailsData['data']['soil'] ?? $detailsData['soil'] ?? 'Unknown',
                                'temperature' => $detailsData['data']['temperature'] ?? $detailsData['temperature'] ?? 'Unknown',
                            ];
                        }

                        return response()->json([
                            'success' => true,
                            'data' => $careDetails
                        ]);
                    } else {
                        Log::error('Details request failed: ' . $detailsResponse->body());
                        return response()->json([
                            'success' => false,
                            'message' => 'Failed to fetch plant details from Trefle API'
                        ], 500);
                    }
                } else {
                    Log::info('No plants found in search results');
                    return response()->json([
                        'success' => false,
                        'message' => 'Plant not found in Trefle database'
                    ]);
                }
            } else {
                Log::error('Search request failed: ' . $searchResponse->body());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to search Trefle database'
                ], 500);
            }
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
