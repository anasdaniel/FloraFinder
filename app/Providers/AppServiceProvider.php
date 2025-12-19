<?php

namespace App\Providers;

use App\Events\PodcastProcessed;
use App\Listeners\SendPodcastEmailListener;
use App\Listeners\SendPodcastNotification;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Event::listen(
            PodcastProcessed::class,
            [
                SendPodcastNotification::class,
                SendPodcastEmailListener::class
            ]
        );

        // URL::forceScheme('https');
    }
}
