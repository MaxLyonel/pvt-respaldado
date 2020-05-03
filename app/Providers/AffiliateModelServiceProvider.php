<?php

namespace App\Providers;

use App\Affiliate;
use App\Observers\AffiliateObserver;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AffiliateModelServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        if (Schema::connection('platform')->hasTable('affiliates')) Affiliate::observe(AffiliateObserver::class);
    }
}
