<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Osiset\ShopifyApp\Contracts\Queries\Shop as IShopQuery;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;
use Osiset\ShopifyApp\Util;

/**
 * Drop-in replacement for Osiset\ShopifyApp\Http\Middleware\IframeProtection.
 *
 * The original caches the full shop Eloquent model, which can come back as a
 * PHP __PHP_Incomplete_Class on unserialize (relations/lazy attributes don't
 * always round-trip through serialize()). This caches just the domain string
 * it actually needs instead.
 */
class IframeProtection
{
    protected IShopQuery $shopQuery;

    public function __construct(IShopQuery $shopQuery)
    {
        $this->shopQuery = $shopQuery;
    }

    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $ancestors = Util::getShopifyConfig('iframe_ancestors');

        $domain = Cache::remember(
            'frame-ancestors-domain_' . $request->input('shop'),
            now()->addMinutes(20),
            function () use ($request) {
                return $this->shopQuery->getByDomain(ShopDomain::fromRequest($request))?->name;
            }
        );

        $domain = $domain ?: '*.myshopify.com';

        $iframeAncestors = "frame-ancestors https://$domain https://admin.shopify.com";

        if (!blank($ancestors)) {
            $iframeAncestors .= ' ' . $ancestors;
        }

        $response->headers->set(
            'Content-Security-Policy',
            $iframeAncestors
        );

        return $response;
    }
}
