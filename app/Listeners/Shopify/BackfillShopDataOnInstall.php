<?php

namespace App\Listeners\Shopify;

use App\Jobs\Shopify\BackfillShopCollectionsJob;
use App\Jobs\Shopify\BackfillShopCustomersJob;
use App\Jobs\Shopify\BackfillShopDiscountsJob;
use App\Jobs\Shopify\BackfillShopOrdersJob;
use App\Jobs\Shopify\BackfillShopProductsJob;
use App\Services\SyncProgressTracker;
use Osiset\ShopifyApp\Messaging\Events\AppInstalledEvent;

/**
 * Fires whenever a shop completes OAuth/token-exchange (fresh install, or a
 * later reinstall) - see Osiset\ShopifyApp\Actions\AuthenticateShop. Queues
 * the full backfill for every synced entity, so any store using this app
 * gets its whole catalog/customer/order history synced automatically, with
 * no manual command required.
 *
 * Customers is queued before Orders (both async, but Laravel processes a
 * queue roughly FIFO) so order->customer_id has a better chance of resolving
 * on first pass; either way BackfillShopOrdersJob leaves it null if not yet
 * found and a later customers sync doesn't retroactively fix it - this is a
 * best-effort ordering, not a guarantee.
 *
 * Safe to fire more than once per shop over its lifetime (e.g. reinstall):
 * every backfill job is upsert-based, so re-running just refreshes data.
 */
class BackfillShopDataOnInstall
{
    public function handle(AppInstalledEvent $event): void
    {
        $shopId = $event->shopId->toNative();

        SyncProgressTracker::start($shopId);

        BackfillShopProductsJob::dispatch($shopId);
        BackfillShopCustomersJob::dispatch($shopId);
        BackfillShopCollectionsJob::dispatch($shopId);
        BackfillShopOrdersJob::dispatch($shopId)->delay(now()->addSeconds(5));
        BackfillShopDiscountsJob::dispatch($shopId);
    }
}
