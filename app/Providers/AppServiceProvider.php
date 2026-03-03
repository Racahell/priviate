<?php

namespace App\Providers;

use App\Models\TutoringSession;
use App\Models\User;
use App\Models\Invoice;
use App\Models\TentorAvailability;
use App\Observers\TutoringSessionObserver;
use App\Observers\GeneralObserver;
use Illuminate\Support\ServiceProvider;

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
        TutoringSession::observe(TutoringSessionObserver::class);
        
        // Audit Logging
        User::observe(GeneralObserver::class);
        Invoice::observe(GeneralObserver::class);
        TentorAvailability::observe(GeneralObserver::class);
        TutoringSession::observe(GeneralObserver::class);
    }
}
