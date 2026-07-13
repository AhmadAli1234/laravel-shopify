<?php

namespace App\Jobs\Shopify;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;

/**
 * Handles the products/create and products/update webhooks.
 */
class SyncProductJob implements ShouldQueue
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

        $product = Product::updateOrCreate(
            [
                'shop_id' => $shop->id,
                'shopify_product_id' => $this->data->id,
            ],
            [
                'title' => $this->data->title ?? null,
                'handle' => $this->data->handle ?? null,
                'vendor' => $this->data->vendor ?? null,
                'product_type' => $this->data->product_type ?? null,
                'status' => $this->data->status ?? null,
                'image_url' => $this->data->image->src ?? ($this->data->images[0]->src ?? null),
                'shopify_updated_at' => $this->data->updated_at ?? null,
                'synced_at' => now(),
            ]
        );

        foreach ($this->data->variants ?? [] as $variant) {
            ProductVariant::updateOrCreate(
                ['shopify_variant_id' => $variant->id],
                [
                    'product_id' => $product->id,
                    'shopify_inventory_item_id' => $variant->inventory_item_id ?? null,
                    'sku' => $variant->sku ?? null,
                    'price' => $variant->price ?? null,
                    'inventory_quantity' => $variant->inventory_quantity ?? null,
                    'synced_at' => now(),
                ]
            );
        }
    }
}
