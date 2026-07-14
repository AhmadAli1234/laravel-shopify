<?php

namespace App\Providers;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Osiset\ShopifyApp\Actions\VerifyThemeSupport as VendorVerifyThemeSupport;
use Osiset\ShopifyApp\Http\Middleware\IframeProtection as VendorIframeProtection;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(VendorIframeProtection::class, \App\Http\Middleware\IframeProtection::class);
        $this->app->bind(VendorVerifyThemeSupport::class, \App\Actions\VerifyThemeSupport::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Older MySQL/MariaDB (common on shared hosting) can't index a full
        // varchar(255) column under utf8mb4 - 255 chars x 4 bytes = 1020
        // bytes, over the ~1000 byte index key limit. This is the standard
        // Laravel fix: default to 191 chars (x4 = 764 bytes) for indexed
        // string columns so migrations like password_reset_tokens (email as
        // primary key) don't fail with "Specified key was too long".
        Schema::defaultStringLength(191);

        $this->applyDatabaseDrivenAppUrl();
    }

    /**
     * config/shopify-app.php's 'webhooks' addresses are built with an empty
     * placeholder base (config files load before the DB connection exists,
     * so they can't read AppSetting directly). Substitute in the real,
     * database-configured App URL here instead, now that the DB is
     * available. Guarded for artisan commands (e.g. the very first
     * `migrate`) that run before the app_settings table even exists yet.
     */
    protected function applyDatabaseDrivenAppUrl(): void
    {
        if (! Schema::hasTable('app_settings')) {
            return;
        }

        $appUrl = rtrim(AppSetting::current()->app_url ?? '', '/');
        $webhooks = config('shopify-app.webhooks', []);

        foreach ($webhooks as $key => $webhook) {
            if (! Str::startsWith($webhook['address'], ['http://', 'https://'])) {
                $webhooks[$key]['address'] = $appUrl.$webhook['address'];
            }
        }

        config(['shopify-app.webhooks' => $webhooks]);
    }
}
