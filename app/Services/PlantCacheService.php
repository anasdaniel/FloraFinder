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
               * Fetch and cache care details from Trefle API
               */
              public function refreshCareDetails(Plant $plant): bool
              {
                            $token = config('services.trefle.api_key');
                            if (!$token) {
                                          Log::warning('Trefle API key not configured');
                                          return false;
                            }

                            try {
                                          // Search for the plant by scientific name
                                          $searchResponse = Http::get(self::TREFLE_API_URL . '/plants/search', [
                                                        'token' => $token,
                                                        'q' => $plant->scientific_name,
                                          ]);

                                          if (!$searchResponse->successful()) {
                                                        Log::warning('Trefle search failed for: ' . $plant->scientific_name);
                                                        return false;
                                          }

                                          $searchData = $searchResponse->json();
                                          if (empty($searchData['data'])) {
                                                        Log::info('No Trefle results for: ' . $plant->scientific_name);
                                                        $plant->care_cached_at = now();
                                                        $plant->save();
                                                        return false;
                                          }

                                          // Get the first matching plant
                                          $plantData = $searchData['data'][0];
                                          $plantSlug = $plantData['slug'] ?? null;

                                          if (!$plantSlug) {
                                                        return false;
                                          }

                                          // Fetch detailed plant info
                                          $detailResponse = Http::get(self::TREFLE_API_URL . "/plants/{$plantSlug}", [
                                                        'token' => $token,
                                          ]);

                                          if (!$detailResponse->successful()) {
                                                        Log::warning('Trefle detail fetch failed for: ' . $plantSlug);
                                                        return false;
                                          }

                                          $detailData = $detailResponse->json();
                                          $data = $detailData['data'] ?? [];

                                          // Extract and cache the care details
                                          $this->updatePlantFromTrefle($plant, $data);

                                          return true;
                            } catch (\Exception $e) {
                                          Log::error('Trefle API error: ' . $e->getMessage());
                                          return false;
                            }
              }

              /**
               * Update plant model with Trefle API data
               */
              private function updatePlantFromTrefle(Plant $plant, array $data): void
              {
                            $mainSpec = $data['main_species'] ?? $data;
                            $growth = $mainSpec['growth'] ?? [];
                            $specifications = $mainSpec['specifications'] ?? [];

                            // Basic identifiers
                            $plant->genus = $data['genus']['name'] ?? $plant->genus;
                            $plant->gbif_id = $mainSpec['gbif_id'] ?? $plant->gbif_id;
                            $plant->powo_id = $mainSpec['powo_id'] ?? $plant->powo_id;

                            // Update family if not set
                            if (!$plant->family && isset($data['family']['name'])) {
                                          $plant->family = $data['family']['name'];
                            }

                            // Description from observations or bibliography
                            if (!$plant->description) {
                                          $plant->description = $data['observations'] ?? $mainSpec['observations'] ?? null;
                            }

                            // Conservation status
                            if (isset($mainSpec['specifications']['toxicity'])) {
                                          // Could map toxicity or other specs
                            }

                            // Growth details
                            $plant->sowing = $growth['sowing'] ?? null;
                            $plant->days_to_harvest = $growth['days_to_harvest'] ?? null;
                            $plant->row_spacing_cm = isset($growth['row_spacing']['cm']) ? (int)$growth['row_spacing']['cm'] : null;
                            $plant->spread_cm = isset($growth['spread']['cm']) ? (int)$growth['spread']['cm'] : null;
                            $plant->ph_minimum = $growth['ph_minimum'] ?? null;
                            $plant->ph_maximum = $growth['ph_maximum'] ?? null;
                            $plant->light = $growth['light'] ?? null;
                            $plant->atmospheric_humidity = $growth['atmospheric_humidity'] ?? null;

                            // Monthly cycles
                            $plant->growth_months = $growth['growth_months'] ?? null;
                            $plant->bloom_months = $growth['bloom_months'] ?? null;
                            $plant->fruit_months = $growth['fruit_months'] ?? null;

                            // Precipitation
                            if (isset($growth['minimum_precipitation']['mm'])) {
                                          $plant->minimum_precipitation_mm = (int)$growth['minimum_precipitation']['mm'];
                            }
                            if (isset($growth['maximum_precipitation']['mm'])) {
                                          $plant->maximum_precipitation_mm = (int)$growth['maximum_precipitation']['mm'];
                            }

                            // Temperature
                            if (isset($growth['minimum_temperature']['deg_c'])) {
                                          $plant->minimum_temperature_celsius = (int)$growth['minimum_temperature']['deg_c'];
                            }
                            if (isset($growth['maximum_temperature']['deg_c'])) {
                                          $plant->maximum_temperature_celsius = (int)$growth['maximum_temperature']['deg_c'];
                            }

                            // Soil requirements
                            $plant->soil_nutriments = $growth['soil_nutriments'] ?? null;
                            $plant->soil_salinity = $growth['soil_salinity'] ?? null;
                            $plant->soil_texture = $growth['soil_texture'] ?? null;
                            $plant->soil_humidity = $growth['soil_humidity'] ?? null;

                            // Mark as cached
                            $plant->care_cached_at = now();
                            $plant->save();
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
               */
              public function forceRefresh(Plant $plant): bool
              {
                            $plant->care_cached_at = null;
                            $plant->save();
                            return $this->refreshCareDetails($plant);
              }
}
