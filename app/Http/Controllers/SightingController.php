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
            $zone = Zone::where('zone_name', 'Sabah')->first();
        } elseif ($longitude >= 109 && $longitude <= 116 && $latitude >= 1 && $latitude <= 5) {
            $zone = Zone::where('zone_name', 'Sarawak')->first();
        } elseif ($longitude >= 99 && $longitude <= 105 && $latitude >= 1 && $latitude <= 7) {
            // For Peninsular, we could be more specific if we had more data, 
            // but for now let's just return a generic one or try to match by state if we had state boundaries.
            // Since we don't have boundaries, we'll just return null or a generic Peninsular zone if it existed.
            return null;
        }

        return $zone?->id;
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
