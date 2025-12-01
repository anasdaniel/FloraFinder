<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PlantProxyController extends Controller
{
    public function iucn(Request $request)
    {
        try {
            $name = $request->query('name');

            if (empty($name)) {
                return response()->json(['error' => 'Plant name is required'], 400);
            }

            $apiKey = config('services.iucn.key');

            if (empty($apiKey)) {
                Log::error('IUCN API key is missing in configuration');
                return response()->json(['error' => 'API configuration error'], 500);
            }

            Log::info("Calling IUCN API for plant: {$name}");
            $url = "https://apiv3.iucnredlist.org/api/v3/species/" . urlencode($name) . "?token=$apiKey";

            $response = Http::get($url);

            if (!$response->successful()) {
                Log::warning("IUCN API error: {$response->status()} - {$response->body()}");
                return response()->json([
                    'error' => 'IUCN API error',
                    'status' => $response->status(),
                    'details' => $response->json() ?: $response->body()
                ], $response->status());
            }

            $responseData = $response->json();
            Log::info("IUCN API response received for {$name}");

            return response()->json($responseData);
        } catch (\Exception $e) {
            Log::error("Exception in IUCN API proxy: " . $e->getMessage());
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }

    public function trefle(Request $request)
    {
        try {
            $name = $request->query('name');

            if (empty($name)) {
                return response()->json(['error' => 'Plant name is required'], 400);
            }

            $cacheKey = 'trefle_' . md5($name);
            $cachedData = Cache::get($cacheKey);

            if ($cachedData) {
                Log::info("Returning cached Trefle data for {$name}");
                return response()->json($cachedData);
            }

            $apiKey = config('services.trefle.key');

            if (empty($apiKey)) {
                Log::error('Trefle API key is missing in configuration');
                return response()->json(['error' => 'API configuration error'], 500);
            }

            Log::info("Calling Trefle API for plant: {$name}");
            $url = "https://trefle.io/api/v1/species/search?q=" . urlencode($name) . "&token=$apiKey";

            $response = Http::get($url);

            if (!$response->successful()) {
                Log::warning("Trefle API error: {$response->status()} - {$response->body()}");
                return response()->json([
                    'error' => 'Trefle API error',
                    'status' => $response->status(),
                    'details' => $response->json() ?: $response->body()
                ], $response->status());
            }

            $responseData = $response->json();
            Log::info("Trefle API response received for {$name}");

            // Cache for 1 hour
            Cache::put($cacheKey, $responseData, 3600);

            return response()->json($responseData);
        } catch (\Exception $e) {
            Log::error("Exception in Trefle API proxy: " . $e->getMessage());
            return response()->json(['error' => 'Internal server error', 'message' => $e->getMessage()], 500);
        }
    }
}
