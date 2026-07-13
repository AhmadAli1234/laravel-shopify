<?php

namespace App\Jobs\Shopify;

use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderLineItem;
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
 * Handles orders/create, orders/updated, orders/cancelled, orders/paid,
 * orders/fulfilled, orders/partially_fulfilled - all carry the full order
 * payload, so they're all handled identically (upsert current state).
 */
class SyncOrderJob implements ShouldQueue
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

        $customerId = null;
        if (isset($this->data->customer->id)) {
            $customerId = Customer::where('shop_id', $shop->id)
                ->where('shopify_customer_id', $this->data->customer->id)
                ->value('id');
        }

        $order = Order::updateOrCreate(
            [
                'shop_id' => $shop->id,
                'shopify_order_id' => $this->data->id,
            ],
            [
                'customer_id' => $customerId,
                'name' => $this->data->name ?? null,
                'email' => $this->data->email ?? null,
                'phone' => $this->data->phone ?? null,
                'currency' => $this->data->currency ?? null,
                'total_price' => $this->data->total_price ?? null,
                'financial_status' => $this->data->financial_status ?? null,
                'fulfillment_status' => $this->data->fulfillment_status ?? null,
                'shipping_address' => isset($this->data->shipping_address) ? (array) $this->data->shipping_address : null,
                'billing_address' => isset($this->data->billing_address) ? (array) $this->data->billing_address : null,
                'processed_at' => $this->data->processed_at ?? null,
                'cancelled_at' => $this->data->cancelled_at ?? null,
                'closed_at' => $this->data->closed_at ?? null,
                'shopify_updated_at' => $this->data->updated_at ?? null,
                'synced_at' => now(),
            ]
        );

        foreach ($this->data->line_items ?? [] as $lineItem) {
            $productId = isset($lineItem->product_id)
                ? Product::where('shop_id', $shop->id)->where('shopify_product_id', $lineItem->product_id)->value('id')
                : null;

            $variantId = isset($lineItem->variant_id)
                ? ProductVariant::where('shopify_variant_id', $lineItem->variant_id)->value('id')
                : null;

            OrderLineItem::updateOrCreate(
                ['order_id' => $order->id, 'shopify_line_item_id' => $lineItem->id],
                [
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'title' => $lineItem->title ?? null,
                    'sku' => $lineItem->sku ?? null,
                    'quantity' => $lineItem->quantity ?? null,
                    'price' => $lineItem->price ?? null,
                ]
            );
        }
    }
}
