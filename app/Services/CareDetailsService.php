<?php

namespace App\Services;

use App\Models\Plant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Http\Integrations\GeminiConnector;
use App\Http\Integrations\GeminiRequest;

class CareDetailsService
{
    private const TREFLE_API_URL = 'https://trefle.io/api/v1';
    // Using gemini-2.5-flash as the latest fast model
    private const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
    private const CACHE_DURATION_HOURS = 24;
    private const DB_CACHE_DAYS = 7;
    private const THREAT_CACHE_HOURS = 24;

    /**
     * Get care details for a plant, checking DB first, then preferred provider, then fallback.
     *
     * @param string $scientificName
     * @param string|null $commonName
     * @param string|null $family
     * @param string $preferredProvider 'gemini' (default) or 'trefle'
     * @param bool $forceRefresh Skip cache and fetch fresh data
     */
    public function getCareDetails(
        string $scientificName,
        ?string $commonName = null,
        ?string $family = null,
        string $preferredProvider = 'gemini',
        bool $forceRefresh = false
    ): array {
        // Validate and sanitize scientific name (primary identifier)
        $scientificName = trim($scientificName);
        if (empty($scientificName)) {
            Log::warning('getCareDetails called with empty scientific name');
            return [
                'success' => false,
                'source' => 'none',
                'data' => [],
                'message' => 'Scientific name is required to fetch care details',
            ];
        }

        // Sanitize optional parameters
        $commonName = $commonName ? trim($commonName) : null;
        $commonName = $commonName === '' ? null : $commonName;
        $family = $family ? trim($family) : null;
        $family = $family === '' ? null : $family;

        Log::info("=== CareDetailsService: Getting care for {$scientificName} (provider: {$preferredProvider}, force: " . ($forceRefresh ? 'yes' : 'no') . ") ===");

        $cacheKey = 'care_details_' . md5($scientificName . '_' . $preferredProvider);

        // Check Redis cache first (short-term) - skip if forcing refresh
        if (!$forceRefresh) {
            $cached = Cache::get($cacheKey);
            if ($cached && isset($cached['success'])) {
                Log::info("Returning Redis cached care details for {$scientificName} (source: " . ($cached['source'] ?? 'unknown') . ")");
                return $cached;
            }
        }

        // Check database for existing plant with care details
        $plant = Plant::where('scientific_name', $scientificName)->first();

        // If we have DB cache from the requested provider and it's not stale, use it
        if (!$forceRefresh && $plant && $plant->hasCareDetails() && !$plant->needsCareRefresh()) {
            // Check if we have data from the requested provider
            $currentSource = $plant->getCareSource();

            // If the plant has data from the requested provider, return it immediately
            if ($currentSource === $preferredProvider) {
                Log::info("Returning DB cached care details for {$scientificName} (source: {$currentSource})");
                $result = [
                    'success' => true,
                    'source' => $currentSource,
                    'data' => $plant->getCareDetails(),
                ];
                Cache::put($cacheKey, $result, now()->addHours(self::CACHE_DURATION_HOURS));
                return $result;
            }

            // If forceRefresh is false and we have ANY recent care data,
            // we can try to fetch from the new provider without marking as "forced"
            Log::info("Plant has {$currentSource} data, attempting to fetch {$preferredProvider} data...");
        }

        // Only make API call if forced or we don't have data from this provider
        if ($forceRefresh || !$plant || !$plant->hasCareDetails() || $plant->getCareSource() !== $preferredProvider) {
            Log::info("Fetching fresh care details for {$scientificName} from {$preferredProvider}");
        } else {
            // We have data but it's stale, so we'll try to refresh
            Log::info("Refreshing stale care details for {$scientificName}");
        }

        // Determine provider order based on preference
        $providers = $preferredProvider === 'trefle'
            ? ['trefle', 'gemini']
            : ['gemini', 'trefle'];

        foreach ($providers as $provider) {
            $providerResult = $provider === 'gemini'
                ? $this->fetchFromGemini($scientificName, $commonName, $family)
                : $this->fetchFromTrefle($scientificName);

            if ($providerResult && $this->hasUsefulData($providerResult)) {
                $result = [
                    'success' => true,
                    'source' => $provider,
                    'data' => $providerResult,
                ];

                // Store in Redis cache for fast access (24 hours)
                Cache::put($cacheKey, $result, now()->addHours(self::CACHE_DURATION_HOURS));

                // Only store in database if:
                // 1. We're forcing a refresh (user explicitly requested new data)
                // 2. OR we don't have any plant record yet
                // 3. OR the plant's current source matches the provider we just fetched
                // This prevents overwriting good DB data when just switching providers temporarily
                $shouldStoreInDb = $forceRefresh ||
                    !$plant ||
                    !$plant->hasCareDetails() ||
                    $plant->getCareSource() === $provider;

                if ($shouldStoreInDb) {
                    $this->storeCareDetails($scientificName, $commonName, $family, $providerResult, $provider);
                    Log::info("Stored {$provider} care details in database for {$scientificName}");
                } else {
                    Log::info("Cached {$provider} care details in Redis (not overwriting DB with {$plant->getCareSource()} data)");
                }

                return $result;
            }

            Log::info("{$provider} has no data for {$scientificName}, trying next provider");
        }

        // No data from either source
        return [
            'success' => false,
            'source' => 'none',
            'data' => [],
            'message' => 'Care details not available from any provider',
        ];
    }

    public function inferThreatStatus(string $scientificName): array
    {
        $scientificName = trim($scientificName);
        if ($scientificName === '') {
            return [
                'success' => false,
                'source' => 'gemini',
                'category' => null,
                'reasoning' => 'Scientific name is required',
            ];
        }

        $cacheKey = 'threat_status_' . md5($scientificName);
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        try {
            $prompt = <<<EOT
You are a botanist consultant. Given a plant scientific name, determine its IUCN Red List conservation status category.

Plant: {$scientificName}

Return ONLY valid JSON in this exact format:
{"category":"<IUCN_CODE>","reason":"<brief explanation>"}

Valid IUCN codes:
- EX (Extinct)
- EW (Extinct in the Wild)
- CR (Critically Endangered)
- EN (Endangered)
- VU (Vulnerable)
- NT (Near Threatened)
- LC (Least Concern)
- DD (Data Deficient)
- NE (Not Evaluated)

IMPORTANT: For Rafflesia species and other rare endemic species, use CR, EN, or VU as appropriate based on their known conservation status. Do NOT guess LC unless you're confident the species is common.
EOT;

            $connector = new GeminiConnector();
            $request = new GeminiRequest('gemini-2.5-flash', [
                ['parts' => [['text' => $prompt]]],
            ]);

            $response = $connector->send($request);

            if (!$response->successful()) {
                Log::warning("Gemini threat status request failed for {$scientificName}", ['status' => $response->status(), 'body' => $response->body()]);
                return [
                    'success' => false,
                    'source' => 'gemini',
                    'category' => null,
                    'reasoning' => 'Gemini request failed',
                ];
            }

            $json = $response->json();
            $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? null;

            // Clean markdown formatting if present
            $text = preg_replace('/^```json\s*/i', '', $text ?? '');
            $text = preg_replace('/^```\s*/i', '', $text ?? '');
            $text = preg_replace('/\s*```$/', '', $text ?? '');
            $text = trim($text);

            $parsed = $text ? json_decode($text, true) : null;
            $category = strtoupper(trim($parsed['category'] ?? ''));

            $allowed = ['EX', 'EW', 'CR', 'EN', 'VU', 'NT', 'LC', 'DD', 'NE'];
            if (!in_array($category, $allowed, true)) {
                Log::warning("Invalid IUCN category '{$category}' returned for {$scientificName}, defaulting to NE");
                $category = 'NE';
            }

            $result = [
                'success' => true,
                'source' => 'gemini',
                'category' => $category,
                'reasoning' => $parsed['reason'] ?? 'Inferred from Gemini',
            ];

            Log::info("Inferred IUCN category for {$scientificName}: {$category}", ['reasoning' => $result['reasoning']]);
            Cache::put($cacheKey, $result, now()->addHours(self::THREAT_CACHE_HOURS));

            return $result;
        } catch (\Exception $e) {
            Log::error("Threat status inference failed for {$scientificName}: " . $e->getMessage());
            return [
                'success' => false,
                'source' => 'gemini',
                'category' => null,
                'reasoning' => 'Exception during Gemini request',
            ];
        }
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

        // Update common_name from Gemini if not already set and Gemini provided one
        if (!$plant->common_name && !empty($careData['common_name']) && $careData['common_name'] !== 'null') {
            $plant->common_name = $careData['common_name'];
        }

        // Update malay_name from Gemini if not already set and Gemini provided one
        if (!$plant->malay_name && !empty($careData['malay_name']) && $careData['malay_name'] !== 'null') {
            $plant->malay_name = $careData['malay_name'];
        }

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
     * Fetch care details from Trefle API using scientific name
     */
    private function fetchFromTrefle(string $scientificName): ?array
    {
        $token = config('services.trefle.api_key');
        if (!$token) {
            Log::warning('Trefle API key not configured');
            return null;
        }

        // Validate scientific name
        $scientificName = trim($scientificName);
        if (empty($scientificName)) {
            Log::warning('Scientific name is empty, cannot fetch from Trefle');
            return null;
        }

        try {
            // Search for the plant by scientific name (primary identifier)
            Log::info("Searching Trefle API for scientific name: {$scientificName}");
            $searchResponse = Http::timeout(30)->get(self::TREFLE_API_URL . '/plants/search', [
                'token' => $token,
                'q' => $scientificName,
            ]);

            if (!$searchResponse->successful()) {
                Log::warning('Trefle search failed for: ' . $scientificName . ' (status: ' . $searchResponse->status() . ')');
                return null;
            }

            $searchData = $searchResponse->json();
            if (empty($searchData['data']) || !is_array($searchData['data'])) {
                Log::info('No Trefle results for: ' . $scientificName);
                return null;
            }

            // Get the first matching plant - prefer exact scientific name match
            $plantData = null;
            foreach ($searchData['data'] as $result) {
                $resultScientificName = $result['scientific_name'] ?? '';
                if (strcasecmp($resultScientificName, $scientificName) === 0) {
                    $plantData = $result;
                    Log::info("Found exact scientific name match in Trefle: {$resultScientificName}");
                    break;
                }
            }

            // If no exact match, use the first result
            if (!$plantData) {
                $plantData = $searchData['data'][0];
                Log::info("Using first Trefle result (no exact match): " . ($plantData['scientific_name'] ?? 'unknown'));
            }

            $plantSlug = $plantData['slug'] ?? null;
            if (empty($plantSlug)) {
                Log::warning('Trefle result missing slug for: ' . $scientificName);
                return null;
            }

            // Fetch detailed plant info
            $detailResponse = Http::timeout(30)->get(self::TREFLE_API_URL . "/plants/{$plantSlug}", [
                'token' => $token,
            ]);

            if (!$detailResponse->successful()) {
                Log::warning('Trefle detail fetch failed for: ' . $plantSlug . ' (status: ' . $detailResponse->status() . ')');
                return null;
            }

            $detailData = $detailResponse->json();
            $data = $detailData['data'] ?? [];

            if (empty($data)) {
                Log::warning('Trefle returned empty data for: ' . $plantSlug);
                return null;
            }

            return $this->sanitizeCareData($this->parseTrefleData($data));
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
     * Uses scientific name with common name context in a single optimized call
     */
    private function fetchFromGemini(string $scientificName, ?string $commonName = null, ?string $family = null): ?array
    {
        $apiKey = config('services.gemini.api_key');
        if (!$apiKey) {
            Log::warning('Gemini API key not configured');
            return null;
        }

        // Validate scientific name is not empty
        $scientificName = trim($scientificName);
        if (empty($scientificName)) {
            Log::warning('Scientific name is empty, cannot fetch care details');
            return null;
        }

        // Single optimized call - include common name context if available
        $commonName = $commonName ? trim($commonName) : null;
        Log::info("Fetching care details from Gemini for: {$scientificName}" . ($commonName ? " ({$commonName})" : ''));

        $result = $this->callGeminiAPI($scientificName, $commonName, $family, $apiKey);

        if ($result && $this->hasUsefulData($result)) {
            Log::info("✓ Gemini care details fetched successfully");
            return $this->sanitizeCareData($result);
        }

        Log::info("✗ Gemini failed for: {$scientificName}");
        return null;
    }

    /**
     * Sanitize care data to ensure all expected fields exist with proper defaults
     */
    private function sanitizeCareData(array $data): array
    {
        $defaults = [
            'common_name' => null,
            'malay_name' => null,
            'description' => null,
            'watering_guide' => null,
            'sunlight_guide' => null,
            'soil_guide' => null,
            'temperature_guide' => null,
            'care_summary' => null,
            'care_tips' => null,
            'sowing' => null,
            'days_to_harvest' => null,
            'row_spacing_cm' => null,
            'spread_cm' => null,
            'ph_minimum' => null,
            'ph_maximum' => null,
            'light' => null,
            'atmospheric_humidity' => null,
            'growth_months' => null,
            'bloom_months' => null,
            'fruit_months' => null,
            'minimum_precipitation_mm' => null,
            'maximum_precipitation_mm' => null,
            'minimum_temperature_celsius' => null,
            'maximum_temperature_celsius' => null,
            'soil_nutriments' => null,
            'soil_salinity' => null,
            'soil_texture' => null,
            'soil_humidity' => null,
            'watering_frequency' => null,
        ];

        // Merge with defaults, ensuring all keys exist
        $sanitized = array_merge($defaults, $data);

        // Trim string values and convert empty strings to null
        foreach ($sanitized as $key => $value) {
            if (is_string($value)) {
                $value = trim($value);
                $sanitized[$key] = $value === '' ? null : $value;
            }
        }

        return $sanitized;
    }

    /**
     * Make the actual Gemini API call with retry logic for transient failures
     */
    private function callGeminiAPI(string $scientificName, ?string $commonName = null, ?string $family = null, string $apiKey): ?array
    {
        $maxRetries = 2;
        $retryDelayMs = 300; // milliseconds - faster retry

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            $result = $this->attemptGeminiAPICall($scientificName, $commonName, $family, $apiKey, $attempt, $maxRetries);

            if ($result !== null || $attempt === $maxRetries) {
                return $result;
            }

            // Short delay before retry (300ms, then 600ms)
            Log::info("Gemini API attempt {$attempt} failed, retrying in {$retryDelayMs}ms...");
            usleep($retryDelayMs * 1000);
            $retryDelayMs *= 2;
        }

        return null;
    }

    /**
     * Single attempt to call Gemini API
     */
    private function attemptGeminiAPICall(string $scientificName, ?string $commonName, ?string $family, string $apiKey, int $attempt, int $maxRetries): ?array
    {
        try {
            // Build plant identification - scientific name is always primary
            $plantIdentification = "Scientific name: {$scientificName}";
            if (!empty($commonName)) {
                $plantIdentification .= " (also known as: {$commonName})";
            }
            $familyInfo = !empty($family) ? " from the {$family} family" : '';

            $prompt = <<<PROMPT
You are a botanical expert. Provide care guidance for: {$plantIdentification}{$familyInfo}.

Return ONLY valid JSON (no markdown, no backticks):
{"common_name":"the most widely used common/vernacular name for this plant, or null if unknown","malay_name":"the common name in Malaysia (Bahasa Melayu), or null if not applicable","description":"1-2 sentences about the plant","watering_guide":"watering frequency and tips","sunlight_guide":"light requirements","soil_guide":"soil type and pH","temperature_guide":"temperature range and humidity","care_summary":"key care point in 1 sentence","care_tips":"2-3 practical tips"}

Be concise but informative. Never leave fields empty. For common_name, provide the most recognized English common name if one exists. For malay_name, provide the most common name used in Malaysia.
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
            $isRetryable = str_contains($e->getMessage(), 'timeout')
                || str_contains($e->getMessage(), '429')
                || str_contains($e->getMessage(), '503')
                || str_contains($e->getMessage(), 'rate limit');

            if ($isRetryable && $attempt < $maxRetries) {
                Log::warning("Gemini API transient error (attempt {$attempt}): " . $e->getMessage());
                return null; // Will trigger retry
            }

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
            $req = new \App\Http\Integrations\GeminiRequest('gemini-2.0-flash', [
                ['parts' => [['text' => $prompt]]]
            ], null); // null = no responseMimeType, returns plain text
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
     * Generate a bot reply (chat) for a plant via Gemini with retry logic
     */
    public function generateBotReply(string $plantName, array $history, string $message): string
    {
        $apiKey = config('services.gemini.api_key');
        if (!$apiKey) {
            Log::warning('Gemini API key not configured');
            return "I'm having trouble checking my botanical reference books right now.";
        }

        // Validate and sanitize inputs
        $plantName = trim($plantName);
        $message = trim($message);
        if (empty($plantName) || empty($message)) {
            return "I need to know which plant you're asking about.";
        }

        // Build contents array with proper Gemini format
        // System instruction as first user message (Gemini doesn't have a system role)
        $systemPrompt = "You are a friendly botanist assistant helping with the plant: \"{$plantName}\". Be concise, practical, and helpful. No markdown.";
        $contents = [
            ['role' => 'user', 'parts' => [['text' => $systemPrompt]]],
            ['role' => 'model', 'parts' => [['text' => "I'd be happy to help you with {$plantName}! What would you like to know?"]]],
        ];

        // Add validated history
        foreach ($history as $msg) {
            if (!isset($msg['role'], $msg['text']) || !is_string($msg['text'])) {
                continue; // Skip malformed entries
            }
            $role = $msg['role'] === 'user' ? 'user' : 'model';
            $text = trim($msg['text']);
            if (!empty($text)) {
                $contents[] = ['role' => $role, 'parts' => [['text' => $text]]];
            }
        }

        // Add current user message
        $contents[] = ['role' => 'user', 'parts' => [['text' => $message]]];

        // Retry logic for transient failures
        $maxRetries = 2;
        $retryDelayMs = 300;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                $connector = new GeminiConnector();
                $req = new GeminiRequest('gemini-2.0-flash', $contents, null);
                $response = $connector->send($req);

                if (!$response->successful()) {
                    $status = $response->status();
                    Log::warning("Gemini chat request failed (attempt {$attempt}): status {$status}");

                    // Retry on rate limit or server errors
                    if (($status === 429 || $status >= 500) && $attempt < $maxRetries) {
                        usleep($retryDelayMs * 1000);
                        $retryDelayMs *= 2;
                        continue;
                    }
                    return "I'm having trouble checking my botanical reference books right now.";
                }

                $data = $response->json();
                $text = $data['candidates'][0]['content']['parts'][0]['text'] ?? null;

                if ($text) {
                    return trim($text);
                }

                Log::warning('Gemini chat returned empty text');
                return "I couldn't find specific information about that. Try asking differently!";
            } catch (\Exception $e) {
                Log::error("Gemini chat error (attempt {$attempt}): " . $e->getMessage());

                if ($attempt < $maxRetries) {
                    usleep($retryDelayMs * 1000);
                    $retryDelayMs *= 2;
                    continue;
                }
            }
        }

        return "I'm having trouble checking my botanical reference books right now.";
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
