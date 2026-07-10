<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * VerifyShopify (vendor) only looks for the shop domain in the URL, headers,
 * or referer - it never falls back to the session-authenticated shop. That
 * means a valid logged-in session still fails once the URL's own "shop"/
 * "token" params are missing or expired (e.g. a bookmark, or a plain reload
 * after the session token expires).
 *
 * This runs before verify.shopify and fills in "shop" from the current
 * session's authenticated user, if the URL didn't already provide one, so
 * VerifyShopify can fall into its normal re-authentication flow instead of
 * treating the request as having no shop context at all.
 */
class InferShopFromSession
{
    public function handle(Request $request, Closure $next)
    {
        if (! $request->filled('shop') && $request->user()) {
            $request->query->set('shop', $request->user()->getDomain()->toNative());
        }

        return $next($request);
    }
}
