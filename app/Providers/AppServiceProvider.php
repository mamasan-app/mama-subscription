<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament;
use Illuminate\Database\Eloquent\Model;
use App\Models\Service;
use App\Observers\ServiceObserver;
use App\Observers\SubscriptionObserver;
use App\Models\Subscription;

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
        //Model::unguard(true);
        Service::observe(ServiceObserver::class);
        Subscription::observe(SubscriptionObserver::class);
    }
}
