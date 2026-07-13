<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Singleton row (always id=1) holding the Shopify app credentials, so they
 * can be configured/updated via the Settings page instead of editing .env.
 */
class AppSetting extends Model
{
    protected $fillable = [
        'shopify_api_key',
        'shopify_api_secret',
        'shopify_api_scopes',
        'app_url',
    ];

    protected $hidden = [
        'shopify_api_secret',
    ];

    public static function current(): self
    {
        return static::firstOrNew(['id' => 1]);
    }

    public function isConfigured(): bool
    {
        return filled($this->shopify_api_key)
            && filled($this->shopify_api_secret)
            && filled($this->app_url);
    }
}
