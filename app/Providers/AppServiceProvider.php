<?php

namespace App\Providers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Observers\PlanObserver;
use App\Observers\SubscriptionObserver;
use Illuminate\Database\Eloquent\Model;
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
        // Model::unguard(true);

        Plan::observe(PlanObserver::class);
        Subscription::observe(SubscriptionObserver::class);
    }
}
