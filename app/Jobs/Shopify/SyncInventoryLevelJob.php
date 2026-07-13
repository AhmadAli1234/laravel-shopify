<?php

namespace App\Jobs\Shopify;

use App\Models\InventoryLevel;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;

/**
 * Handles the inventory_levels/update webhook.
 */
class SyncInventoryLevelJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public string $shopDomain,
        public object $data,
    ) {
    }

    public function handle(): void
    {
        $shop = User::where('name', ShopDomain::fromNative($this->shopDomain)->toNative())->first();

        if (! $shop) {
            return;
        }

        $variant = ProductVariant::whereHas('product', fn ($query) => $query->where('shop_id', $shop->id))
            ->where('shopify_inventory_item_id', $this->data->inventory_item_id)
            ->first();

        if (! $variant) {
            // Item not tracked yet (e.g. webhook arrived before the owning
            // product was synced) - a later full sync will pick it up.
            return;
        }

        InventoryLevel::updateOrCreate(
            [
                'product_variant_id' => $variant->id,
                'shopify_location_id' => $this->data->location_id,
            ],
            [
                'available' => $this->data->available ?? null,
                'synced_at' => now(),
            ]
        );

        $variant->update([
            'inventory_quantity' => $variant->inventoryLevels()->sum('available'),
        ]);
    }
}
