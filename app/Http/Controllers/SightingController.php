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
use Inertia\Inertia;

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
            'organs.*' => 'nullable|string|in:flower,leaf,fruit,bark,auto',
            'organ_scores' => 'nullable|array',
            'organ_scores.*' => 'nullable|integer|between:0,100',
            'save_to_collection' => 'nullable|string|in:0,1',
            'report_sighting' => 'nullable|string|in:0,1',
        ]);

        $saveToCollection = $request->input('save_to_collection', '1') === '1';
        $reportSighting = $request->input('report_sighting', '1') === '1';

        // Find or create plant record with cached care details
        $plant = $this->plantCacheService->findOrCreateWithCare(
            $validated['scientific_name'],
            $validated['common_name'] ?? null,
            $validated['family'] ?? null,
            $validated['genus'] ?? null,
            $validated['gbif_id'] ?? null,
            $validated['powo_id'] ?? null,
            $validated['iucn_category'] ?? null
        );

        $images = $request->file('images');
        $organs = $validated['organs'] ?? [];
        $organScores = $validated['organ_scores'] ?? [];
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
                'organ' => $organs[$index] ?? 'auto',
                'organ_score' => isset($organScores[$index]) ? (int) $organScores[$index] : null,
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
                    'organ_score' => $img['organ_score'],
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
                    'organ_score' => $img['organ_score'],
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
        // Rough bounding boxes for Malaysian states
        $states = [
            'Sabah' => ['lat' => [4.0, 7.5], 'lng' => [115.0, 119.5]],
            'Sarawak' => ['lat' => [0.8, 5.0], 'lng' => [109.5, 115.8]],
            'Perlis' => ['lat' => [6.3, 6.7], 'lng' => [100.1, 100.3]],
            'Kedah' => ['lat' => [5.0, 6.5], 'lng' => [100.2, 101.0]],
            'Pulau Pinang' => ['lat' => [5.1, 5.6], 'lng' => [100.1, 100.6]],
            'Perak' => ['lat' => [3.6, 6.0], 'lng' => [100.4, 101.8]],
            'Kelantan' => ['lat' => [4.5, 6.3], 'lng' => [101.3, 102.7]],
            'Terengganu' => ['lat' => [3.9, 5.9], 'lng' => [102.4, 103.6]],
            'Pahang' => ['lat' => [2.5, 4.8], 'lng' => [101.3, 103.6]],
            'Selangor' => ['lat' => [2.6, 3.9], 'lng' => [100.7, 101.9]],
            'Kuala Lumpur' => ['lat' => [3.0, 3.3], 'lng' => [101.6, 101.8]],
            'Negeri Sembilan' => ['lat' => [2.4, 3.3], 'lng' => [101.8, 102.8]],
            'Melaka' => ['lat' => [2.0, 2.5], 'lng' => [102.0, 102.6]],
            'Johor' => ['lat' => [1.2, 2.8], 'lng' => [102.5, 104.5]],
        ];

        foreach ($states as $name => $bounds) {
            if (
                $latitude >= $bounds['lat'][0] && $latitude <= $bounds['lat'][1] &&
                $longitude >= $bounds['lng'][0] && $longitude <= $bounds['lng'][1]
            ) {
                $zone = Zone::where('zone_name', $name)->first();
                if ($zone) {
                    return $zone->id;
                }
            }
        }

        return null;
    }

    /**
     * Get all sightings for the current user.
     */
    public function index(Request $request)
    {
        $query = Sighting::with(['user', 'images', 'zone', 'plant'])
            ->where('user_id', Auth::id())
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('scientific_name', 'like', "%{$search}%")
                    ->orWhere('common_name', 'like', "%{$search}%");
            });
        }

        // Apply region filter
        if ($request->filled('region')) {
            $query->where('region', $request->input('region'));
        }

        // Apply conservation status filter
        if ($request->filled('status')) {
            $status = $request->input('status');
            $query->whereHas('plant', function ($q) use ($status) {
                $q->where('iucn_category', $status);
            });
        }

        // Apply date filters
        if ($request->filled('date_from')) {
            $query->whereDate('sighted_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('sighted_at', '<=', $request->input('date_to'));
        }

        $sightings = $query->latest('sighted_at')->paginate(12)->withQueryString();

        // Get unique regions for filter dropdown
        $regions = Sighting::where('user_id', Auth::id())
            ->whereNotNull('region')
            ->distinct()
            ->pluck('region')
            ->sort()
            ->values()
            ->toArray();

        // Get unique IUCN categories for filter dropdown
        $statuses = Sighting::where('user_id', Auth::id())
            ->with('plant') // Eager load to access plant relationship
            ->get()
            ->pluck('plant.iucn_category')
            ->filter() // Remove nulls
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        return Inertia::render('Sightings/Index', [
            'sightings' => $sightings,
            'regions' => $regions,
            'statuses' => $statuses,
            'filters' => $request->only(['search', 'region', 'status', 'date_from', 'date_to']),
        ]);
    }

    /**
     * Get a specific sighting.
     */
    public function show(Sighting $sighting)
    {
        // Ensure user can only view their own sightings
        if ($sighting->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        $sighting->load(['user', 'images', 'zone', 'plant']);

        return Inertia::render('Sightings/Show', [
            'sighting' => $sighting,
        ]);
    }

    /**
     * Delete a sighting.
     */
    public function destroy(Sighting $sighting)
    {
        // Ensure user can only delete their own sightings
        if ($sighting->user_id !== Auth::id()) {
            abort(403, 'Unauthorized');
        }

        // Delete associated images from storage
        foreach ($sighting->images as $image) {
            $path = str_replace('/storage/', '', $image->image_url);
            Storage::disk('public')->delete($path);
        }

        // Delete the sighting (images will be cascade deleted)
        $sighting->delete();

        return redirect()->route('sightings.index')->with('success', 'Sighting deleted successfully.');
    }

    /**
     * Public map view showing all sightings (not just current user's).
     */
    public function publicMap(Request $request)
    {
        $query = Sighting::with(['user', 'images', 'zone', 'plant'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude');

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('scientific_name', 'like', "%{$search}%")
                    ->orWhere('common_name', 'like', "%{$search}%");
            });
        }

        // Apply region filter
        if ($request->filled('region')) {
            $query->where('region', $request->input('region'));
        }

        // Apply family filter
        if ($request->filled('family')) {
            $family = $request->input('family');
            $query->whereHas('plant', function ($q) use ($family) {
                $q->where('family', $family);
            });
        }

        // Apply conservation status filter (multiple)
        if ($request->filled('statuses')) {
            $statuses = $request->input('statuses');
            if (is_array($statuses) && count($statuses) > 0) {
                $query->whereHas('plant', function ($q) use ($statuses) {
                    $q->whereIn('iucn_category', $statuses);
                });
            }
        }

        // Apply date filters
        if ($request->filled('date_from')) {
            $query->whereDate('sighted_at', '>=', $request->input('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('sighted_at', '<=', $request->input('date_to'));
        }

        $sightings = $query->latest('sighted_at')->paginate(12)->withQueryString();

        // Get unique regions for filter dropdown
        $regions = Sighting::whereNotNull('region')
            ->distinct()
            ->pluck('region')
            ->sort()
            ->values()
            ->toArray();

        // Get unique plant families for filter dropdown
        $families = Sighting::with('plant')
            ->get()
            ->pluck('plant.family')
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        // Conservation status options
        $conservationStatuses = [
            ['code' => 'NE', 'label' => 'Not Evaluated'],
            ['code' => 'DD', 'label' => 'Data Deficient'],
            ['code' => 'LC', 'label' => 'Least Concern'],
            ['code' => 'NT', 'label' => 'Near Threatened'],
            ['code' => 'VU', 'label' => 'Vulnerable'],
            ['code' => 'EN', 'label' => 'Endangered'],
            ['code' => 'CR', 'label' => 'Critically Endangered'],
        ];

        // Calculate statistics
        $allSightings = Sighting::with('plant')->get();
        $stats = [
            'total_sightings' => $allSightings->count(),
            'unique_species' => $allSightings->pluck('scientific_name')->unique()->count(),
            'unique_families' => $allSightings->pluck('plant.family')->filter()->unique()->count(),
            'unique_regions' => $allSightings->pluck('region')->filter()->unique()->count(),
        ];

        return Inertia::render('Sightings/PublicMap', [
            'sightings' => $sightings,
            'regions' => $regions,
            'families' => $families,
            'conservationStatuses' => $conservationStatuses,
            'filters' => $request->only(['search', 'region', 'family', 'statuses', 'date_from', 'date_to']),
            'stats' => $stats,
        ]);
    }
}
