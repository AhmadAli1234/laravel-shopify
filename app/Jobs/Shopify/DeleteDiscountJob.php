<?php

namespace App\Jobs\Shopify;

use App\Models\Discount;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;

/**
 * Handles the discounts/delete webhook.
 */
class DeleteDiscountJob implements ShouldQueue
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
        $gid = $this->data->admin_graphql_api_id ?? null;

        if (! $shop || ! $gid) {
            return;
        }

        Discount::where('shop_id', $shop->id)
            ->where('shopify_discount_gid', $gid)
            ->delete();
    }
}
