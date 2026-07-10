<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Osiset\ShopifyApp\Http\Middleware\IframeProtection as VendorIframeProtection;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(VendorIframeProtection::class, \App\Http\Middleware\IframeProtection::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
