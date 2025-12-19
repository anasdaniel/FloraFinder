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
    public function findOrCreateWithCare(
        string $scientificName,
        ?string $commonName = null,
        ?string $family = null,
        ?string $genus = null,
        ?string $gbifId = null,
        ?string $powoId = null,
        ?string $iucnCategory = null,
        ?string $imageUrl = null,
        ?array $referenceImages = null
    ): Plant {
        // Try to find existing plant
        $plant = Plant::where('scientific_name', $scientificName)->first();

        if (!$plant) {
            // Create new plant record with all available data
            $plant = Plant::create([
                'scientific_name' => $scientificName,
                'common_name' => $commonName,
                'family' => $family,
                'genus' => $genus,
                'gbif_id' => $gbifId,
                'powo_id' => $powoId,
                'iucn_category' => $iucnCategory,
                'image_url' => $imageUrl,
                'reference_images' => $referenceImages,
            ]);
        } else {
            // Update fields if provided and currently missing
            $updated = false;

            if ($commonName && !$plant->common_name) {
                $plant->common_name = $commonName;
                $updated = true;
            }
            if ($imageUrl && !$plant->image_url) {
                $plant->image_url = $imageUrl;
                $updated = true;
            }
            if ($referenceImages && empty($plant->reference_images)) {
                $plant->reference_images = $referenceImages;
                $updated = true;
            }
            if ($family && !$plant->family) {
                $plant->family = $family;
                $updated = true;
            }
            if ($genus && !$plant->genus) {
                $plant->genus = $genus;
                $updated = true;
            }
            if ($gbifId && !$plant->gbif_id) {
                $plant->gbif_id = $gbifId;
                $updated = true;
            }
            if ($powoId && !$plant->powo_id) {
                $plant->powo_id = $powoId;
                $updated = true;
            }
            if ($iucnCategory && !$plant->iucn_category) {
                $plant->iucn_category = $iucnCategory;
                $updated = true;
            }

            if ($updated) {
                $plant->save();
            }
        }

        // Refresh care details if needed
        if ($plant->needsCareRefresh()) {
            $this->refreshCareDetails($plant);
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
    public function getCareDetails(
        string $scientificName,
        ?string $commonName = null,
        ?string $family = null,
        ?string $genus = null,
        ?string $gbifId = null,
        ?string $powoId = null,
        ?string $iucnCategory = null,
        ?string $imageUrl = null,
        ?array $referenceImages = null,
        string $preferredProvider = 'gemini',
        bool $forceRefresh = false
    ): array {
        $plant = $this->findOrCreateWithCare(
            $scientificName,
            $commonName,
            $family,
            $genus,
            $gbifId,
            $powoId,
            $iucnCategory,
            $imageUrl,
            $referenceImages
        );

        if ($forceRefresh || $plant->needsCareRefresh() || ($preferredProvider !== $plant->getCareSource() && $preferredProvider !== 'none')) {
            $this->refreshCareDetails($plant, $preferredProvider);
            $plant->refresh();
        }

        return [
            'success' => $plant->hasCareDetails(),
            'source' => $plant->getCareSource(),
            'data' => $plant->getCareDetails(),
        ];
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
