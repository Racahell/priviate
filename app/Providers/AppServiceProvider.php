<?php

namespace App\Providers;

use App\Models\TutoringSession;
use App\Models\User;
use App\Models\Invoice;
use App\Models\Item;
use App\Models\Dispute;
use App\Models\MenuPermission;
use App\Models\Payment;
use App\Models\Package;
use App\Models\Subject;
use App\Models\TentorAvailability;
use App\Models\TeacherPayout;
use App\Models\WebSetting;
use App\Observers\TutoringSessionObserver;
use App\Observers\GeneralObserver;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

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
        Item::observe(GeneralObserver::class);
        MenuPermission::observe(GeneralObserver::class);
        Payment::observe(GeneralObserver::class);
        Package::observe(GeneralObserver::class);
        Dispute::observe(GeneralObserver::class);
        Subject::observe(GeneralObserver::class);
        TentorAvailability::observe(GeneralObserver::class);
        TeacherPayout::observe(GeneralObserver::class);
        TutoringSession::observe(GeneralObserver::class);
        WebSetting::observe(GeneralObserver::class);

        View::composer('*', function ($view) {
            $setting = null;

            try {
                if (Schema::hasTable('web_settings')) {
                    $setting = WebSetting::query()->first();
                }
            } catch (\Throwable $e) {
                $setting = null;
            }

            $view->with('webSetting', $setting);
            $view->with('webLogo', $setting?->logo_url);
            $view->with('webFooter', data_get($setting, 'extra.footer_content'));
            $view->with('webFooterConfig', data_get($setting, 'extra.footer_config', []));
            $view->with('webName', $setting?->site_name ?: config('app.name', 'PrivTuition'));
        });
    }
}
