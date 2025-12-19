<?php

namespace App\Http\Controllers;

use App\Models\Plant;
use App\Services\PlantCacheService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PlantController extends Controller
{
    public function __construct(
        private PlantCacheService $plantCacheService
    ) {}

    /**
     * Display a listing of plants with care details.
     */
    public function index(Request $request)
    {
        $query = Plant::query();

        // Search filter
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('scientific_name', 'like', "%{$search}%")
                    ->orWhere('common_name', 'like', "%{$search}%")
                    ->orWhere('family', 'like', "%{$search}%");
            });
        }

        // Family filter
        if ($request->filled('family')) {
            $query->where('family', $request->input('family'));
        }

        // Endangered/threatened species filter
        if ($request->boolean('is_endangered')) {
            $query->whereIn('iucn_category', ['EX', 'EW', 'CR', 'EN', 'VU', 'NT']);
        }

        $plants = $query->orderBy('scientific_name')->paginate(20);

        // Load the latest sighting image for each plant, or use the API image as fallback
        $plants->getCollection()->transform(function ($plant) {
            $latestSighting = $plant->sightings()
                ->whereNotNull('image_url')
                ->latest('sighted_at')
                ->first();

            $plant->latest_image = $latestSighting?->image_url ?? $plant->image_url;
            return $plant;
        });

        // Get unique families for filter dropdown
        $families = Plant::whereNotNull('family')
            ->distinct()
            ->pluck('family')
            ->sort()
            ->values();

        return Inertia::render('Plants/Index', [
            'plants' => $plants,
            'families' => $families,
            'filters' => $request->only(['search', 'family', 'is_endangered']),
        ]);
    }

    /**
     * Display the specified plant with full care details.
     */
    public function show(Plant $plant)
    {
        // Refresh care details if needed
        if ($plant->needsCareRefresh()) {
            $this->plantCacheService->refreshCareDetails($plant);
            $plant->refresh();
        }

        // Get sighting count for this plant
        $sightingCount = $plant->sightings()->count();

        // Get recent sightings
        $recentSightings = $plant->sightings()
            ->with('user:id,name')
            ->latest('sighted_at')
            ->take(5)
            ->get();

        // Get all sighting images for this plant
        $sightingImages = \App\Models\SightingImage::whereIn('sighting_id', $plant->sightings()->pluck('id'))
            ->latest()
            ->pluck('image_url')
            ->toArray();

        return Inertia::render('Plants/Show', [
            'plant' => $plant,
            'sightingCount' => $sightingCount,
            'recentSightings' => $recentSightings,
            'sightingImages' => $sightingImages,
        ]);
    }

    /**
     * Force refresh care details for a plant.
     */
    public function refreshCare(Request $request, Plant $plant)
    {
        $provider = $request->input('provider', 'gemini'); // Default to gemini

        $success = $this->plantCacheService->forceRefresh($plant, $provider);

        if ($success) {
            return redirect()->back()->with('success', 'Care details refreshed successfully!');
        }

        return redirect()->back()->with('error', 'Could not refresh care details. Please try again later.');
    }
}
