<?php

namespace App\Console\Commands;

use App\Http\Integrations\GetINaturalistObservationsRequest;
use App\Http\Integrations\INaturalistConnector;
use App\Models\Plant;
use App\Models\SeasonalAlert;
use Illuminate\Console\Command;

class SyncSeasonalAlerts extends Command
{
    protected $signature = 'alerts:sync-inaturalist';
    protected $description = 'Sync seasonal alerts from iNaturalist API';

    public function handle()
    {
        $this->info('Starting iNaturalist sync...');

        // Mapping of Malaysian states to iNaturalist place_ids
        $states = [
            'Penang' => 12486,
            'Johor' => 12487,
            'Pahang' => 12488,
            'Perak' => 12489,
            'Selangor' => 12490,
        ];

        // Key plants to track
        $plants = Plant::whereIn('common_name', ['Durian', 'Rambutan', 'Mangosteen'])
            ->whereNotNull('scientific_name')
            ->get();

        $connector = new INaturalistConnector();

        foreach ($plants as $plant) {
            foreach ($states as $stateName => $placeId) {
                // Check for Fruiting (term_id=12, term_value_id=14)
                $this->syncPhenology($connector, $plant, $stateName, $placeId, '14', 'fruiting');

                // Check for Flowering (term_id=12, term_value_id=13)
                $this->syncPhenology($connector, $plant, $stateName, $placeId, '13', 'flowering');
            }
        }

        $this->info('Sync completed!');
    }

    private function syncPhenology($connector, $plant, $stateName, $placeId, $termValueId, $type)
    {
        $request = new GetINaturalistObservationsRequest(
            $plant->scientific_name,
            $placeId,
            '12',
            $termValueId
        );

        $response = $connector->send($request);

        if ($response->successful()) {
            $total = $response->json('total_results');

            if ($total >= 3) { // Threshold for alert
                SeasonalAlert::updateOrCreate([
                    'plant_id' => $plant->id,
                    'state' => $stateName,
                    'type' => $type,
                ], [
                    'title' => "{$plant->common_name} {$type} in {$stateName}",
                    'description' => "Multiple observations of {$plant->common_name} in {$type} stage reported in {$stateName}. Early season warning!",
                    'observation_count' => $total,
                    'source' => 'api',
                    'is_active' => true,
                    'starts_at' => now(),
                    'ends_at' => now()->addWeeks(2),
                ]);

                $this->line("Alert created for {$plant->common_name} ({$type}) in {$stateName}");
            }
        }
    }
}
