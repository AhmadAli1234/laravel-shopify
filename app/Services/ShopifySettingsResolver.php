<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Config;

/**
 * Wired up as config('shopify-app.config_api_callback') - the package's own
 * sanctioned extension point for sourcing 'api_*' config keys dynamically
 * (see Osiset\ShopifyApp\Util::getShopifyConfig). api_key/api_secret/
 * api_scopes are sourced strictly from the database (edited via the
 * Settings page) - deliberately NOT falling back to .env, so there's a
 * single source of truth and no chance of the app silently running on
 * stale/wrong .env values after they've been changed in Settings. If the
 * database has nothing configured, these simply resolve empty, which is
 * what makes EnsureShopifyConfigured correctly treat the app as
 * unauthorized/unconfigured rather than quietly working off .env.
 *
 * Every other 'api_*' key (api_version, api_redirect, api_grant_mode, ...)
 * still passes through to config/.env as normal - those are deployment
 * settings, not credentials, and were never meant to move to Settings.
 *
 * Must be a static method reference (not a Closure) in config, since
 * `php artisan config:cache` can't serialize closures.
 */
class ShopifySettingsResolver
{
    private static ?AppSetting $cached = null;

    private const DATABASE_ONLY_KEYS = ['api_key', 'api_secret', 'api_scopes'];

    public static function resolve(string $key, $shop = null)
    {
        if (in_array($key, self::DATABASE_ONLY_KEYS, true)) {
            $setting = self::$cached ??= AppSetting::current();

            return match ($key) {
                'api_key' => $setting->shopify_api_key,
                'api_secret' => $setting->shopify_api_secret,
                'api_scopes' => $setting->shopify_api_scopes,
            };
        }

        return Config::get("shopify-app.{$key}");
    }
}
