<?php

namespace App\Jobs;

use App\Models\Plant;
use App\Services\CareDetailsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class FetchPlantCareDetails implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected Plant $plant,
        protected string $preferredProvider = 'gemini'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(CareDetailsService $careDetailsService): void
    {
        Log::info("Background job: Fetching care details for {$this->plant->scientific_name}");

        $result = $careDetailsService->getCareDetails(
            $this->plant->scientific_name,
            $this->plant->common_name,
            $this->plant->family,
            $this->preferredProvider,
            true // force refresh
        );

        if ($result['success']) {
            Log::info("Background job: Successfully fetched care details for {$this->plant->scientific_name}");
        } else {
            Log::warning("Background job: Failed to fetch care details for {$this->plant->scientific_name}");
        }
    }
}
