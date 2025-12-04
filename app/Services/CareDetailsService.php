<?php

namespace App\Services;

use App\Models\Plant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CareDetailsService
{
    private const TREFLE_API_URL = 'https://trefle.io/api/v1';
    // Using gemini-2.5-flash as the latest fast model
    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
    private const CACHE_DURATION_HOURS = 24;
    private const DB_CACHE_DAYS = 7;

    /**
     * Get care details for a plant, checking DB first, then Gemini, then Trefle
     */
    public function getCareDetails(string $scientificName, ?string $commonName = null, ?string $family = null): array
    {
        Log::info("=== CareDetailsService: Getting care for {$scientificName} ===");

        $cacheKey = 'care_details_' . md5($scientificName);

        // Check Redis cache first (short-term)
        $cached = Cache::get($cacheKey);
        if ($cached) {
            Log::info("Returning cached care details for {$scientificName} (source: {$cached['source']})");
            return $cached;
        }

        // Check database for existing plant with care details
        $plant = Plant::where('scientific_name', $scientificName)->first();

        if ($plant && $plant->hasCareDetails() && !$plant->needsCareRefresh()) {
            Log::info("Returning DB cached care details for {$scientificName} (source: {$plant->getCareSource()})");
            $result = [
                'success' => true,
                'source' => $plant->getCareSource(),
                'data' => $plant->getCareDetails(),
            ];
            Cache::put($cacheKey, $result, now()->addHours(self::CACHE_DURATION_HOURS));
            return $result;
        }

        Log::info("No cache found, fetching fresh care details for {$scientificName}");

        // Try Gemini first
        $geminiResult = $this->fetchFromGemini($scientificName, $commonName, $family);

        if ($geminiResult && $this->hasUsefulData($geminiResult)) {
            // Store in database
            $this->storeCareDetails($scientificName, $commonName, $family, $geminiResult, 'gemini');

            $result = [
                'success' => true,
                'source' => 'gemini',
                'data' => $geminiResult,
            ];
            Cache::put($cacheKey, $result, now()->addHours(self::CACHE_DURATION_HOURS));
            return $result;
        }

        // Fallback to Trefle
        Log::info("Gemini has no data for {$scientificName}, trying Trefle");
        $trefleResult = $this->fetchFromTrefle($scientificName);

        if ($trefleResult && $this->hasUsefulData($trefleResult)) {
            // Store in database
            $this->storeCareDetails($scientificName, $commonName, $family, $trefleResult, 'trefle');

            $result = [
                'success' => true,
                'source' => 'trefle',
                'data' => $trefleResult,
            ];
            Cache::put($cacheKey, $result, now()->addHours(self::CACHE_DURATION_HOURS));
            return $result;
        }

        // No data from either source
        return [
            'success' => false,
            'source' => 'none',
            'data' => [],
            'message' => 'Care details not available from any provider',
        ];
    }

    /**
     * Store care details in the database
     */
    private function storeCareDetails(
        string $scientificName,
        ?string $commonName,
        ?string $family,
        array $careData,
        string $source
    ): Plant {
        $plant = Plant::firstOrCreate(
            ['scientific_name' => $scientificName],
            [
                'common_name' => $commonName,
                'family' => $family,
            ]
        );

        // Update care details
        $plant->update([
            'description' => $careData['description'] ?? null,
            'sowing' => $careData['sowing'] ?? null,
            'days_to_harvest' => $careData['days_to_harvest'] ?? null,
            'row_spacing_cm' => $careData['row_spacing_cm'] ?? null,
            'spread_cm' => $careData['spread_cm'] ?? null,
            'ph_minimum' => $careData['ph_minimum'] ?? null,
            'ph_maximum' => $careData['ph_maximum'] ?? null,
            'light' => $careData['light'] ?? null,
            'atmospheric_humidity' => $careData['atmospheric_humidity'] ?? null,
            'growth_months' => $careData['growth_months'] ?? null,
            'bloom_months' => $careData['bloom_months'] ?? null,
            'fruit_months' => $careData['fruit_months'] ?? null,
            'minimum_precipitation_mm' => $careData['minimum_precipitation_mm'] ?? null,
            'maximum_precipitation_mm' => $careData['maximum_precipitation_mm'] ?? null,
            'minimum_temperature_celsius' => $careData['minimum_temperature_celsius'] ?? null,
            'maximum_temperature_celsius' => $careData['maximum_temperature_celsius'] ?? null,
            'soil_nutriments' => $careData['soil_nutriments'] ?? null,
            'soil_salinity' => $careData['soil_salinity'] ?? null,
            'soil_texture' => $careData['soil_texture'] ?? null,
            'soil_humidity' => $careData['soil_humidity'] ?? null,
            'watering_frequency' => $careData['watering_frequency'] ?? null,
            'care_tips' => $careData['care_tips'] ?? null,
            // Gemini text-based fields
            'watering_guide' => $careData['watering_guide'] ?? null,
            'sunlight_guide' => $careData['sunlight_guide'] ?? null,
            'soil_guide' => $careData['soil_guide'] ?? null,
            'temperature_guide' => $careData['temperature_guide'] ?? null,
            'care_summary' => $careData['care_summary'] ?? null,
            'care_cached_at' => now(),
            'care_source' => $source,
        ]);

        Log::info("Stored {$source} care details for {$scientificName}");

        return $plant;
    }
    /**
     * Fetch care details from Trefle API
     */
    private function fetchFromTrefle(string $scientificName): ?array
    {
        $token = config('services.trefle.api_key');
        if (!$token) {
            Log::warning('Trefle API key not configured');
            return null;
        }

        try {
            // Search for the plant by scientific name
            $searchResponse = Http::get(self::TREFLE_API_URL . '/plants/search', [
                'token' => $token,
                'q' => $scientificName,
            ]);

            if (!$searchResponse->successful()) {
                Log::warning('Trefle search failed for: ' . $scientificName);
                return null;
            }

            $searchData = $searchResponse->json();
            if (empty($searchData['data'])) {
                Log::info('No Trefle results for: ' . $scientificName);
                return null;
            }

            // Get the first matching plant
            $plantData = $searchData['data'][0];
            $plantSlug = $plantData['slug'] ?? null;

            if (!$plantSlug) {
                return null;
            }

            // Fetch detailed plant info
            $detailResponse = Http::get(self::TREFLE_API_URL . "/plants/{$plantSlug}", [
                'token' => $token,
            ]);

            if (!$detailResponse->successful()) {
                Log::warning('Trefle detail fetch failed for: ' . $plantSlug);
                return null;
            }

            $detailData = $detailResponse->json();
            $data = $detailData['data'] ?? [];

            return $this->parseTrefleData($data);
        } catch (\Exception $e) {
            Log::error('Trefle API error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse Trefle API response into standardized format
     */
    private function parseTrefleData(array $data): array
    {
        $mainSpec = $data['main_species'] ?? $data;
        $growth = $mainSpec['growth'] ?? [];
        $specifications = $mainSpec['specifications'] ?? [];

        return [
            // Basic info
            'genus' => $data['genus']['name'] ?? null,
            'family' => $data['family']['name'] ?? null,
            'description' => $data['observations'] ?? $mainSpec['observations'] ?? null,

            // Growth details
            'sowing' => $growth['sowing'] ?? null,
            'days_to_harvest' => $growth['days_to_harvest'] ?? null,
            'row_spacing_cm' => isset($growth['row_spacing']['cm']) ? (int)$growth['row_spacing']['cm'] : null,
            'spread_cm' => isset($growth['spread']['cm']) ? (int)$growth['spread']['cm'] : null,
            'ph_minimum' => $growth['ph_minimum'] ?? null,
            'ph_maximum' => $growth['ph_maximum'] ?? null,
            'light' => $growth['light'] ?? null,
            'atmospheric_humidity' => $growth['atmospheric_humidity'] ?? null,

            // Monthly cycles
            'growth_months' => $growth['growth_months'] ?? null,
            'bloom_months' => $growth['bloom_months'] ?? null,
            'fruit_months' => $growth['fruit_months'] ?? null,

            // Precipitation
            'minimum_precipitation_mm' => isset($growth['minimum_precipitation']['mm']) ? (int)$growth['minimum_precipitation']['mm'] : null,
            'maximum_precipitation_mm' => isset($growth['maximum_precipitation']['mm']) ? (int)$growth['maximum_precipitation']['mm'] : null,

            // Temperature
            'minimum_temperature_celsius' => isset($growth['minimum_temperature']['deg_c']) ? (int)$growth['minimum_temperature']['deg_c'] : null,
            'maximum_temperature_celsius' => isset($growth['maximum_temperature']['deg_c']) ? (int)$growth['maximum_temperature']['deg_c'] : null,

            // Soil requirements
            'soil_nutriments' => $growth['soil_nutriments'] ?? null,
            'soil_salinity' => $growth['soil_salinity'] ?? null,
            'soil_texture' => $growth['soil_texture'] ?? null,
            'soil_humidity' => $growth['soil_humidity'] ?? null,
        ];
    }

    /**
     * Fetch care details from Gemini AI - returns descriptive text fields
     */
    private function fetchFromGemini(string $scientificName, ?string $commonName = null, ?string $family = null): ?array
    {
        $apiKey = config('services.gemini.api_key');
        if (!$apiKey) {
            Log::warning('Gemini API key not configured');
            return null;
        }

        Log::info("Fetching care details from Gemini for: {$scientificName}");

        try {
            $plantName = $commonName ? "{$scientificName} ({$commonName})" : $scientificName;
            $familyInfo = $family ? " from the {$family} family" : '';

            $prompt = <<<PROMPT
You are a botanical expert. Provide practical care guidance for the plant: {$plantName}{$familyInfo}.

Return ONLY a valid JSON object with descriptive text fields. Do not include any markdown formatting, backticks, or explanations. Just the raw JSON.

{
    "description": "2-3 sentence description of the plant, its appearance, and origin",
    "watering_guide": "Describe watering needs in detail (frequency, amount, seasonal adjustments, signs of over/under watering)",
    "sunlight_guide": "Describe light requirements (full sun, partial shade, indoor lighting tips, ideal placement)",
    "soil_guide": "Describe ideal soil conditions (type, pH, drainage, amendments, potting mix recommendations)",
    "temperature_guide": "Describe temperature tolerance (ideal ranges, frost sensitivity, humidity preferences, seasonal care)",
    "care_summary": "A brief 1-2 sentence summary of the most important care considerations for this plant",
    "care_tips": "3-4 practical care tips for keeping this plant healthy"
}

Make each field informative and practical for a home gardener. Always provide useful content even for less common plants.
PROMPT;

            // Use Saloon connector/request for Gemini instead of direct HTTP
            $connector = new \App\Http\Integrations\GeminiConnector();
            $geminiRequest = new \App\Http\Integrations\GeminiRequest(
                'gemini-2.5-flash',
                [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            );

            $response = $connector->send($geminiRequest);

            if (!$response->successful()) {
                Log::warning('Gemini API request failed: ' . $response->status() . ' - ' . $response->body());
                return null;
            }

            $responseData = $response->json();
            $text = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if (!$text) {
                Log::warning('Gemini returned empty response');
                return null;
            }

            // Clean up the response - remove markdown code blocks if present
            $text = preg_replace('/^```json\s*/i', '', $text);
            $text = preg_replace('/^```\s*/i', '', $text);
            $text = preg_replace('/\s*```$/', '', $text);
            $text = trim($text);

            $careData = json_decode($text, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::warning('Failed to parse Gemini response as JSON: ' . json_last_error_msg());
                Log::debug('Gemini raw response: ' . $text);
                return null;
            }

            Log::info("Gemini care details fetched successfully for {$scientificName}", [
                'has_watering_guide' => !empty($careData['watering_guide']),
                'has_sunlight_guide' => !empty($careData['sunlight_guide']),
                'has_soil_guide' => !empty($careData['soil_guide']),
                'has_temperature_guide' => !empty($careData['temperature_guide']),
                'has_care_summary' => !empty($careData['care_summary']),
            ]);

            return $careData;
        } catch (\Exception $e) {
            Log::error('Gemini API error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate a short description about the plant via Gemini
     */
    public function generatePlantDescription(string $scientificName): string
    {
        $apiKey = config('services.gemini.api_key');
        if (!$apiKey) {
            Log::warning('Gemini API key not configured');
            return 'Description unavailable.';
        }

        $prompt = "In no more than three sentences, describe the plant {$scientificName}, summarizing its key traits, natural habitat, and notable uses. Write in plain text only.";

        try {
            $connector = new \App\Http\Integrations\GeminiConnector();
            $req = new \App\Http\Integrations\GeminiRequest('gemini-2.5-flash', [
                ['parts' => [['text' => $prompt]]]
            ]);
            $response = $connector->send($req);
            if (!$response->successful()) {
                Log::warning('Gemini generate description failed: ' . $response->status());
                return 'Description unavailable.';
            }
            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            return $text ? trim($text) : 'Description unavailable.';
        } catch (\Exception $e) {
            Log::error('Gemini description error: ' . $e->getMessage());
            return 'Description unavailable.';
        }
    }

    /**
     * Generate a bot reply (chat) for a plant via Gemini
     */
    public function generateBotReply(string $plantName, array $history, string $message): string
    {
        $apiKey = config('services.gemini.api_key');
        if (!$apiKey) {
            Log::warning('Gemini API key not configured');
            return "I'm having trouble checking my botanical reference books right now.";
        }

        $systemPrompt = "You are a helpful botanist assistant. The user has just identified a plant: \"{$plantName}\". Answer their questions specifically about this plant. Keep answers concise, friendly, and practical. Do not use markdown formatting.";
        $contents = [['role' => 'model', 'parts' => [['text' => $systemPrompt]]]];
        foreach ($history as $msg) {
            $contents[] = ['role' => $msg['role'], 'parts' => [['text' => $msg['text']]]];
        }
        $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

        try {
            $connector = new \App\Http\Integrations\GeminiConnector();
            $req = new \App\Http\Integrations\GeminiRequest('gemini-2.5-flash', $contents);
            $response = $connector->send($req);
            if (!$response->successful()) {
                Log::warning('Gemini chat request failed: ' . $response->status());
                return "I'm having trouble checking my botanical reference books right now.";
            }
            $data = $response->json();
            $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
            return $text ?? "I'm having trouble checking my botanical reference books right now.";
        } catch (\Exception $e) {
            Log::error('Gemini chat error: ' . $e->getMessage());
            return "I'm having trouble checking my botanical reference books right now.";
        }
    }
    /**
     * Check if the care details have useful data
     */
    private function hasUsefulData(array $data): bool
    {
        // For Gemini text-based responses, check for any descriptive content
        $textFields = [
            'description',
            'care_tips',
            // New text-based Gemini fields
            'watering_guide',
            'sunlight_guide',
            'soil_guide',
            'temperature_guide',
            'care_summary',
        ];

        foreach ($textFields as $field) {
            if (!empty($data[$field]) && strlen($data[$field]) > 10) {
                return true;
            }
        }

        // For Trefle numeric responses, check for numeric data
        $numericFields = [
            'ph_minimum',
            'ph_maximum',
            'minimum_temperature_celsius',
            'maximum_temperature_celsius',
        ];

        foreach ($numericFields as $field) {
            if (isset($data[$field]) && is_numeric($data[$field])) {
                return true;
            }
        }

        return false;
    }
}
