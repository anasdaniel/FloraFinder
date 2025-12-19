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
                return [
                    'id' => $alert->id,
                    'title' => $alert->title,
                    'description' => $alert->description,
                    'type' => $alert->type === 'flowering' ? 'info' : 'warning',
                    'color' => $alert->type === 'flowering' ? 'blue' : 'orange',
                    'source' => 'api',
                    'observation_count' => $alert->observation_count,
                ];
            });

        // 2. Get Static Alerts from Plant Model (if no live alert exists for that plant)
        $livePlantIds = $liveAlerts->pluck('plant_id')->toArray();

        $staticAlerts = Plant::where(function ($query) use ($currentMonth) {
            $query->whereJsonContains('bloom_months', (string)$currentMonth)
                ->orWhereJsonContains('fruit_months', (string)$currentMonth);
        })
            ->whereNotIn('id', $livePlantIds)
            ->limit(3)
            ->get()
            ->map(function ($plant) use ($currentMonth) {
                $isBlooming = in_array((string)$currentMonth, json_decode($plant->bloom_months ?? '[]'));
                return [
                    'id' => 'static-' . $plant->id,
                    'title' => $plant->common_name . ($isBlooming ? ' Blooming Season' : ' Fruiting Season'),
                    'description' => "It's currently the expected " . ($isBlooming ? 'blooming' : 'fruiting') . " season for " . $plant->common_name . " in Malaysia.",
                    'type' => 'info',
                    'color' => 'blue',
                    'source' => 'static',
                ];
            });

        return $liveAlerts->concat($staticAlerts);
    }
}
