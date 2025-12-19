<?php

namespace App\Services;

use App\Models\Plant;
use App\Models\SeasonalAlert;
use Carbon\Carbon;

class SeasonalAlertService
{
    public function getActiveAlerts($state = null)
    {
        $currentMonth = Carbon::now()->month;

        // 1. Get Live Alerts from DB (populated by Command)
        $liveAlerts = SeasonalAlert::with('plant')
            ->where('is_active', true)
            ->where('ends_at', '>', now())
            ->when($state, fn($q) => $q->where('state', $state))
            ->get()
            ->map(function ($alert) {
                $isFruiting = $alert->type === 'fruiting';
                return [
                    'id' => $alert->id,
                    'title' => $alert->title,
                    'description' => $alert->description,
                    'type' => $isFruiting ? 'peak' : 'starting',
                    'color' => $isFruiting ? 'orange' : 'blue',
                    'source' => 'api',
                    'observation_count' => $alert->observation_count,
                ];
            });

        // 2. Get Static Alerts from Plant Model (if no live alert exists for that plant)
        $livePlantIds = $liveAlerts->pluck('plant_id')->toArray();

        $staticAlerts = Plant::whereNotIn('id', $livePlantIds)
            ->get()
            ->filter(function ($plant) use ($currentMonth) {
                $bloomMonths = is_array($plant->bloom_months) ? $plant->bloom_months : json_decode($plant->bloom_months ?? '[]', true);
                $fruitMonths = is_array($plant->fruit_months) ? $plant->fruit_months : json_decode($plant->fruit_months ?? '[]', true);

                return in_array((string)$currentMonth, $bloomMonths ?: []) ||
                    in_array((string)$currentMonth, $fruitMonths ?: []);
            })
            ->take(3)
            ->map(function ($plant) use ($currentMonth) {
                $bloomMonths = is_array($plant->bloom_months) ? $plant->bloom_months : json_decode($plant->bloom_months ?? '[]', true);
                $isBlooming = in_array((string)$currentMonth, $bloomMonths ?: []);
                return [
                    'id' => 'static-' . $plant->id,
                    'title' => $plant->common_name . ($isBlooming ? ' Blooming Season' : ' Fruiting Season'),
                    'description' => "It's currently the expected " . ($isBlooming ? 'blooming' : 'fruiting') . " season for " . $plant->common_name . " in Malaysia.",
                    'type' => $isBlooming ? 'starting' : 'peak',
                    'color' => $isBlooming ? 'blue' : 'orange',
                    'source' => 'static',
                ];
            });

        return $liveAlerts->concat($staticAlerts);
    }
}
