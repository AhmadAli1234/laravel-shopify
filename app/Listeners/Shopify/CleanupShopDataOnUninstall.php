<?php

namespace App\Listeners\Shopify;

use App\Models\Collection;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Order;
use App\Models\Product;
use Osiset\ShopifyApp\Messaging\Events\AppUninstalledEvent;

/**
 * Fires from Osiset\ShopifyApp\Messaging\Jobs\AppUninstalledJob after it
 * soft-deletes the shop itself. Removes every entity synced for that shop
 * (products cascade to variants/inventory_levels, orders cascade to line
 * items/fulfillments/transactions, collections cascade to their product
 * pivot rows) so uninstalled stores don't leave stale data behind in a
 * multi-tenant setup.
 */
class CleanupShopDataOnUninstall
{
    public function handle(AppUninstalledEvent $event): void
    {
        $shopId = $event->shop->getId()->toNative();

        Product::where('shop_id', $shopId)->delete();
        Customer::where('shop_id', $shopId)->delete();
        Collection::where('shop_id', $shopId)->delete();
        Order::where('shop_id', $shopId)->delete();
        Discount::where('shop_id', $shopId)->delete();
    }
}
