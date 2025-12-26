<?php

namespace App\Http\Controllers;

use App\Models\PlantIdentification;
use App\Models\Sighting;
use App\Services\SeasonalAlertService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function welcome()
    {
        return Inertia::render('Welcome');
    }

    public function dashboard()
    {
        return redirect()->route('welcome-plant');
    }

    public function index(Request $request, SeasonalAlertService $alertService)
    {
        $user = Auth::user();
        $userId = $user->id;

        $range = (int) $request->query('range', 6);

        // Get user's sightings
        $userSightings = Sighting::where('user_id', $userId);
        $totalSightings = $userSightings->count();

        // Get sightings from last month to calculate growth percentage
        $lastMonthSightings = Sighting::where('user_id', $userId)
            ->where('created_at', '>=', Carbon::now()->subMonth())
            ->count();

        $previousMonthSightings = Sighting::where('user_id', $userId)
            ->whereBetween('created_at', [Carbon::now()->subMonths(2), Carbon::now()->subMonth()])
            ->count();

        $sightingsGrowth = $previousMonthSightings > 0
            ? round((($lastMonthSightings - $previousMonthSightings) / $previousMonthSightings) * 100)
            : ($lastMonthSightings > 0 ? 100 : 0);

        // Get unique species/plants detected by user (from their identifications)
        $uniqueSpecies = PlantIdentification::where('user_id', $userId)
            ->distinct()
            ->count('scientific_name');

        // Get unique families from identifications
        $uniqueFamilies = PlantIdentification::where('user_id', $userId)
            ->distinct()
            ->count('family');

        // Get unique regions explored
        $regionsExplored = Sighting::where('user_id', $userId)
            ->whereNotNull('region')
            ->distinct()
            ->pluck('region');

        $topRegion = Sighting::where('user_id', $userId)
            ->whereNotNull('region')
            ->select('region', DB::raw('count(*) as count'))
            ->groupBy('region')
            ->orderByDesc('count')
            ->first();

        // Get conservation impact - count unique endangered species found in identifications
        $endangeredCount = PlantIdentification::where('user_id', $userId)
            ->whereIn('iucn_category', ['EN', 'CR', 'VU']) // Endangered, Critically Endangered, Vulnerable
            ->distinct()
            ->count('scientific_name');

        $conservationImpact = $endangeredCount > 3 ? 'High' : ($endangeredCount > 0 ? 'Medium' : 'Low');

        // Get recent sightings with verification status
        $recentSightings = Sighting::with(['plant', 'images'])
            ->where('user_id', $userId)
            ->orderByDesc('created_at')
            ->take(5)
            ->get()
            ->map(function ($sighting) {
                return [
                    'id' => $sighting->id,
                    'name' => $sighting->scientific_name ?? $sighting->common_name ?? 'Unknown',
                    'common_name' => $sighting->common_name,
                    'date' => $sighting->created_at->format('d M Y'),
                    'family' => $sighting->plant?->family ?? 'Unknown',
                    'location' => $sighting->location_name ?? $sighting->region ?? 'Unknown',
                    'status' => $sighting->plant_id ? 'Verified' : 'Pending ID',
                    'statusClass' => $sighting->plant_id ? 'text-emerald-600' : 'text-amber-500',
                    'image' => $sighting->images->first()?->image_url ?? $sighting->image_url,
                ];
            });

        // Get first detection dates for each species to calculate "New Species" accurately
        $firstDetections = PlantIdentification::where('user_id', $userId)
            ->select('scientific_name', DB::raw('MIN(created_at) as first_detected_at'))
            ->groupBy('scientific_name')
            ->get();

        // Get activity data for the chart - optimized to avoid queries in loop
        $monthStartBoundary = Carbon::now()->subMonths($range - 1)->startOfMonth();
        $sightingsInRange = Sighting::where('user_id', $userId)
            ->where('created_at', '>=', $monthStartBoundary)
            ->get(['created_at']);

        $activityData = [];
        for ($i = $range - 1; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            $sightingsCount = $sightingsInRange->filter(function ($sighting) use ($monthStart, $monthEnd) {
                return $sighting->created_at->between($monthStart, $monthEnd);
            })->count();

            // Count species whose FIRST detection was in this month
            $newSpeciesCount = $firstDetections->filter(function ($detection) use ($monthStart, $monthEnd) {
                $date = Carbon::parse($detection->first_detected_at);
                return $date->between($monthStart, $monthEnd);
            })->count();

            $activityData[] = [
                'month' => $month->format('M'),
                'sightings' => $sightingsCount,
                'newSpecies' => $newSpeciesCount,
            ];
        }

        // Build stats array
        $stats = [
            [
                'label' => 'Total Sightings',
                'value' => (string) $totalSightings,
                'subtext' => ($sightingsGrowth >= 0 ? '+' : '') . $sightingsGrowth . '% this month',
                'subtextClass' => $sightingsGrowth >= 0 ? 'text-emerald-600' : 'text-rose-600',
                'icon' => 'eye',
                'color' => 'text-gray-600',
                'bgColor' => 'bg-gray-50',
                'progress' => min(100, $totalSightings * 2),
            ],
            [
                'label' => 'Species Logged',
                'value' => (string) $uniqueSpecies,
                'subtext' => 'Across ' . $uniqueFamilies . ' families',
                'subtextClass' => 'text-gray-500',
                'icon' => 'leaf',
                'color' => 'text-amber-600',
                'bgColor' => 'bg-amber-50',
                'progress' => min(100, $uniqueSpecies * 5),
            ],
            [
                'label' => 'Regions Explored',
                'value' => (string) $regionsExplored->count(),
                'subtext' => $topRegion ? 'Mostly in ' . $topRegion->region : 'None yet',
                'subtextClass' => 'text-gray-500',
                'icon' => 'map',
                'color' => 'text-blue-600',
                'bgColor' => 'bg-blue-50',
                'progress' => min(100, $regionsExplored->count() * 20),
            ],
            [
                'label' => 'Conservation',
                'value' => (string) $endangeredCount,
                'subtext' => 'Endangered Found',
                'subtextClass' => 'text-rose-600',
                'icon' => 'shield-check',
                'color' => 'text-rose-600',
                'bgColor' => 'bg-rose-50',
                'progress' => $conservationImpact === 'High' ? 85 : ($conservationImpact === 'Medium' ? 50 : 20),
            ],
        ];

        // Get current date info for header
        $currentDate = Carbon::now();
        $greeting = $this->getGreeting();

        // Get map sightings (all sightings with valid coordinates, limit to recent 50)
        $mapSightings = Sighting::with(['plant', 'images'])
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->orderByDesc('created_at')
            ->take(50)
            ->get()
            ->map(function ($sighting) {
                return [
                    'id' => $sighting->id,
                    'latitude' => (float) $sighting->latitude,
                    'longitude' => (float) $sighting->longitude,
                    'scientificName' => $sighting->scientific_name,
                    'commonName' => $sighting->common_name,
                    'location' => $sighting->location_name ?? $sighting->region,
                    'date' => $sighting->created_at->format('d M Y'),
                    'dateTimestamp' => $sighting->created_at->timestamp,
                    'conservationStatus' => $sighting->plant?->iucn_category ?? 'NE',
                    'image' => $sighting->images->first()?->image_url ?? $sighting->image_url,
                ];
            });

        // dd($mapSightings);

        return Inertia::render('Dashboard/Index', [
            'stats' => $stats,
            'recentSightings' => $recentSightings,
            'activityData' => $activityData,
            'greeting' => $greeting,
            'currentDate' => [
                'day' => $currentDate->format('l'),
                'date' => $currentDate->format('d M'),
            ],
            'growthPercentage' => $sightingsGrowth,
            'mapSightings' => $mapSightings,
            'seasonalAlerts' => $alertService->getActiveAlerts($request->state),
            'filters' => [
                'range' => $range,
            ],
        ]);
    }

    private function getGreeting(): string
    {
        $hour = Carbon::now()->hour;

        if ($hour < 12) {
            return 'Morning Update';
        } elseif ($hour < 17) {
            return 'Afternoon Update';
        } else {
            return 'Evening Update';
        }
    }
}
