<?php

namespace App\Http\Middleware;

use App\Models\AppSetting;
use Closure;
use Illuminate\Http\Request;

/**
 * A fresh deployment with no Shopify API credentials configured in the
 * database yet can't do anything useful - verify.shopify and the OAuth flow
 * both need a real api_key/api_secret/app_url to even attempt building a
 * Shopify URL. Credentials are database-only (see ShopifySettingsResolver
 * and AppServiceProvider, no .env fallback), so this is the single gate
 * that catches "not configured" for every route. API/JSON requests get a
 * 401; normal page loads get redirected to the Settings/connect page.
 */
class EnsureShopifyConfigured
{
    /**
     * Paths that must stay reachable even when Shopify isn't configured yet -
     * without these, a fresh unconfigured install would infinite-loop
     * (redirected to /settings, which requires login, but /login itself
     * would otherwise also get redirected to /settings).
     */
    private const EXEMPT_PATTERNS = [
        'settings*',
        'webhook*',
        'up',
        'login',
        'logout',
        'forgot-password',
        'reset-password*',
        'user/*',
    ];

    public function handle(Request $request, Closure $next)
    {
        if ($request->is(self::EXEMPT_PATTERNS)) {
            return $next($request);
        }

        if (! AppSetting::current()->isConfigured()) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthorized: Shopify API credentials are not configured.',
                ], 401);
            }

            return redirect()->route('settings.edit');
        }

        return $next($request);
    }
}
