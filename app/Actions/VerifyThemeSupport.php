<?php

namespace App\Actions;

use Illuminate\Support\Facades\Log;
use Osiset\ShopifyApp\Actions\VerifyThemeSupport as VendorVerifyThemeSupport;
use Osiset\ShopifyApp\Objects\Enums\ThemeSupportLevel;
use Osiset\ShopifyApp\Objects\Values\ShopId;
use Throwable;

/**
 * Vendor's InstallShop only catches \Exception around this action's result,
 * but Osiset\ShopifyApp\Services\ThemeHelper caches a raw Shopify REST
 * response (including the Guzzle response/stream) via the 'database' cache
 * driver in one of its private methods - unserializing that can throw a
 * plain \Error ("Cannot use object of type __PHP_Incomplete_Class as
 * array"), which \Exception does NOT catch, crashing the whole OAuth
 * install. ThemeHelper's fragile bits are private (can't be overridden
 * directly), so this widens the catch one level up instead: any failure
 * here just means "theme support unknown", never worth failing install over.
 */
class VerifyThemeSupport extends VendorVerifyThemeSupport
{
    public function __invoke(ShopId $shopId): int
    {
        try {
            return parent::__invoke($shopId);
        } catch (Throwable $e) {
            Log::warning('Theme support detection failed, defaulting to unsupported: '.$e->getMessage());

            return ThemeSupportLevel::UNSUPPORTED;
        }
    }
}
