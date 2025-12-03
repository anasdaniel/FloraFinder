<?php

namespace App\Http\Controllers;

use App\Models\PlantIdentification;
use App\Models\Sighting;
use App\Models\SightingImage;
use App\Models\Zone;
use App\Services\PlantCacheService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class SightingController extends Controller
{
    public function __construct(
        private PlantCacheService $plantCacheService
    ) {}

    /**
     * Store plant to collection and/or report sighting.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'scientific_name' => 'required|string|max:255',
            'common_name' => 'nullable|string|max:255',
            'family' => 'nullable|string|max:255',
            'genus' => 'nullable|string|max:255',
            'confidence' => 'nullable|numeric|between:0,1',
            'gbif_id' => 'nullable|string|max:255',
            'powo_id' => 'nullable|string|max:255',
            'iucn_category' => 'nullable|string|max:50',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'location_name' => 'nullable|string|max:255',
            'region' => 'nullable|string|max:255',
            'sighted_at' => 'nullable|date',
            'description' => 'nullable|string|max:2000',
            'images' => 'required|array|min:1',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:10240',
            'organs' => 'nullable|array',
            'organs.*' => 'nullable|string|in:flower,leaf,fruit,bark,habit',
            'save_to_collection' => 'nullable|string|in:0,1',
            'report_sighting' => 'nullable|string|in:0,1',
        ]);

        $saveToCollection = $request->input('save_to_collection', '1') === '1';
        $reportSighting = $request->input('report_sighting', '1') === '1';

        // Find or create plant record with cached care details
        $plant = $this->plantCacheService->findOrCreateWithCare(
            $validated['scientific_name'],
            $validated['common_name'] ?? null,
            $validated['family'] ?? null
        );

        $images = $request->file('images');
        $organs = $validated['organs'] ?? [];
        $savedImages = [];

        // Upload all images first
        foreach ($images as $index => $image) {
            $path = $image->store('plant-identifications', 'public');
            $savedImages[] = [
                'path' => $path,
                'url' => Storage::url($path),
                'filename' => $image->getClientOriginalName(),
                'mime_type' => $image->getMimeType(),
                'size' => $image->getSize(),
                'organ' => $organs[$index] ?? 'leaf',
            ];
        }

        $collectionSaved = false;
        $sightingReported = false;

        // Save to collection (PlantIdentification)
        if ($saveToCollection && count($savedImages) > 0) {
            foreach ($savedImages as $img) {
                PlantIdentification::create([
                    'user_id' => Auth::id(),
                    'path' => $img['path'],
                    'url' => $img['url'],
                    'filename' => $img['filename'],
                    'mime_type' => $img['mime_type'],
                    'size' => $img['size'],
                    'organ' => $img['organ'],
                    'scientific_name' => $validated['scientific_name'],
                    'scientific_name_without_author' => $validated['scientific_name'],
                    'common_name' => $validated['common_name'] ?? null,
                    'family' => $validated['family'] ?? null,
                    'genus' => $validated['genus'] ?? null,
                    'confidence' => $validated['confidence'] ?? null,
                    'gbif_id' => $validated['gbif_id'] ?? null,
                    'powo_id' => $validated['powo_id'] ?? null,
                    'iucn_category' => $validated['iucn_category'] ?? null,
                    'region' => $validated['region'] ?? null,
                    'latitude' => $validated['latitude'] ?? null,
                    'longitude' => $validated['longitude'] ?? null,
                ]);
            }
            $collectionSaved = true;
        }

        // Report sighting
        if ($reportSighting) {
            // Auto-detect zone from coordinates
            $zoneId = null;
            if (isset($validated['latitude']) && isset($validated['longitude'])) {
                $zoneId = $this->detectZoneFromCoordinates(
                    $validated['latitude'],
                    $validated['longitude']
                );
            }

            // Create the sighting record
            $sighting = Sighting::create([
                'user_id' => Auth::id(),
                'plant_id' => $plant->id,
                'zone_id' => $zoneId,
                'scientific_name' => $validated['scientific_name'],
                'common_name' => $validated['common_name'] ?? null,
                'latitude' => $validated['latitude'] ?? null,
                'longitude' => $validated['longitude'] ?? null,
                'location_name' => $validated['location_name'] ?? null,
                'region' => $validated['region'] ?? null,
                'sighted_at' => $validated['sighted_at'] ?? now(),
                'description' => $validated['description'] ?? null,
                'image_url' => $savedImages[0]['url'] ?? '',
            ]);

            // Create sighting images
            foreach ($savedImages as $img) {
                SightingImage::create([
                    'sighting_id' => $sighting->id,
                    'image_url' => $img['url'],
                    'organ' => $img['organ'],
                ]);
            }

            $sightingReported = true;
        }

        $message = [];
        if ($collectionSaved) $message[] = 'saved to collection';
        if ($sightingReported) $message[] = 'sighting reported';

        return redirect()->back()->with('success', 'Plant ' . implode(' and ', $message) . '!');
    }

    /**
     * Attempt to detect zone from lat/lng coordinates.
     * Returns zone_id if found, null otherwise.
     */
    private function detectZoneFromCoordinates(float $latitude, float $longitude): ?int
    {
        // Simple region-based zone detection for Malaysian regions
        // This could be enhanced with proper geospatial queries if zones have boundaries

        $regionZoneMap = [
            'Peninsular Malaysia' => 'peninsular',
            'Sabah' => 'sabah',
            'Sarawak' => 'sarawak',
            'Johor' => 'johor',
            'Kedah' => 'kedah',
            'Kelantan' => 'kelantan',
            'Melaka' => 'melaka',
            'Negeri Sembilan' => 'negeri_sembilan',
            'Pahang' => 'pahang',
            'Perak' => 'perak',
            'Perlis' => 'perlis',
            'Pulau Pinang' => 'penang',
            'Selangor' => 'selangor',
            'Terengganu' => 'terengganu',
            'Labuan' => 'labuan',
        ];

        // Basic coordinate-based region detection for Malaysia
        // Sabah: roughly lat 4-7, long 115-119
        // Sarawak: roughly lat 1-5, long 109-116
        // Peninsular: roughly lat 1-7, long 99-105

        if ($longitude >= 115 && $longitude <= 119 && $latitude >= 4 && $latitude <= 7) {
            $zone = Zone::where('zone_name', 'like', '%Sabah%')->first();
        } elseif ($longitude >= 109 && $longitude <= 116 && $latitude >= 1 && $latitude <= 5) {
            $zone = Zone::where('zone_name', 'like', '%Sarawak%')->first();
        } elseif ($longitude >= 99 && $longitude <= 105 && $latitude >= 1 && $latitude <= 7) {
            $zone = Zone::where('zone_name', 'like', '%Peninsular%')
                ->orWhere('zone_name', 'like', '%Peninsula%')
                ->first();
        } else {
            $zone = null;
        }

        return $zone?->id;
    }

    /**
     * Get all sightings (for map display).
     */
    public function index()
    {
        $sightings = Sighting::with(['user', 'images', 'zone'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
            'sightings' => $sightings,
        ]);
    }

    /**
     * Get a specific sighting.
     */
    public function show(Sighting $sighting)
    {
        return response()->json([
            'success' => true,
            'sighting' => $sighting->load(['user', 'images', 'zone']),
        ]);
    }
}
