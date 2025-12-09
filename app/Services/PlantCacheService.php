<?php

namespace App\Services;

use App\Models\Plant;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PlantCacheService
{
    private const TREFLE_API_URL = 'https://trefle.io/api/v1';
    private const CACHE_DURATION_DAYS = 7;

    private CareDetailsService $careDetailsService;

    public function __construct(CareDetailsService $careDetailsService)
    {
        $this->careDetailsService = $careDetailsService;
    }

    /**
     * Find or create a plant by scientific name and ensure care details are cached
     */
    public function findOrCreateWithCare(string $scientificName, ?string $commonName = null, ?string $family = null): Plant
    {
        // Try to find existing plant
        $plant = Plant::where('scientific_name', $scientificName)->first();

        if (!$plant) {
            // Create new plant record
            $plant = Plant::create([
                'scientific_name' => $scientificName,
                'common_name' => $commonName,
                'family' => $family,
            ]);
        }

        // Update common name and family if provided and missing
        if ($commonName && !$plant->common_name) {
            $plant->common_name = $commonName;
        }
        if ($family && !$plant->family) {
            $plant->family = $family;
        }

        // Refresh care details if needed
        if ($plant->needsCareRefresh()) {
            $this->refreshCareDetails($plant);
        }

        if ($plant->isDirty()) {
            $plant->save();
        }

        return $plant;
    }

    /**
     * Fetch and cache care details using CareDetailsService (supports Gemini/Trefle)
     * 
     * @param Plant $plant The plant to refresh
     * @param string $preferredProvider 'gemini' (default) or 'trefle'
     */
    public function refreshCareDetails(Plant $plant, string $preferredProvider = 'gemini'): bool
    {
        try {
            $result = $this->careDetailsService->getCareDetails(
                $plant->scientific_name,
                $plant->common_name,
                $plant->family,
                $preferredProvider,
                true // force refresh
            );

            if ($result['success']) {
                Log::info("Refreshed care details for {$plant->scientific_name} from {$result['source']}");
                $plant->refresh(); // Reload from DB since CareDetailsService already saved
                return true;
            }

            Log::info("No care details available for {$plant->scientific_name}");
            $plant->care_cached_at = now();
            $plant->save();
            return false;
        } catch (\Exception $e) {
            Log::error('Care details refresh error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get care details for a plant, fetching from API if not cached
     */
    public function getCareDetails(string $scientificName, ?string $commonName = null, ?string $family = null): array
    {
        $plant = $this->findOrCreateWithCare($scientificName, $commonName, $family);
        return $plant->getCareDetails();
    }

    /**
     * Force refresh care details for a plant
     * 
     * @param Plant $plant The plant to refresh
     * @param string $preferredProvider 'gemini' (default) or 'trefle'
     */
    public function forceRefresh(Plant $plant, string $preferredProvider = 'gemini'): bool
    {
        $plant->care_cached_at = null;
        $plant->save();
        return $this->refreshCareDetails($plant, $preferredProvider);
    }
}
