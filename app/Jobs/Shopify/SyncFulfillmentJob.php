<?php

namespace App\Jobs\Shopify;

use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;

/**
 * Handles the fulfillments/create and fulfillments/update webhooks.
 */
class SyncFulfillmentJob implements ShouldQueue
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

        if (! $shop || ! isset($this->data->order_id)) {
            return;
        }

        $order = Order::where('shop_id', $shop->id)
            ->where('shopify_order_id', $this->data->order_id)
            ->first();

        if (! $order) {
            return;
        }

        Fulfillment::updateOrCreate(
            [
                'order_id' => $order->id,
                'shopify_fulfillment_id' => $this->data->id,
            ],
            [
                'status' => $this->data->status ?? null,
                'tracking_company' => $this->data->tracking_company ?? null,
                'tracking_number' => $this->data->tracking_number ?? ($this->data->tracking_numbers[0] ?? null),
                'tracking_url' => $this->data->tracking_url ?? ($this->data->tracking_urls[0] ?? null),
                'shopify_updated_at' => $this->data->updated_at ?? null,
                'synced_at' => now(),
            ]
        );
    }
}
