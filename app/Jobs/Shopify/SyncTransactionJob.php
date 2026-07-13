<?php

namespace App\Jobs\Shopify;

use App\Models\Order;
use App\Models\OrderTransaction;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;

/**
 * Handles the order_transactions/create webhook (transactions are
 * effectively immutable once created, so there's no update/delete variant).
 */
class SyncTransactionJob implements ShouldQueue
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

        OrderTransaction::updateOrCreate(
            [
                'order_id' => $order->id,
                'shopify_transaction_id' => $this->data->id,
            ],
            [
                'kind' => $this->data->kind ?? null,
                'status' => $this->data->status ?? null,
                'gateway' => $this->data->gateway ?? null,
                'amount' => $this->data->amount ?? null,
                'processed_at' => $this->data->processed_at ?? null,
                'synced_at' => now(),
            ]
        );
    }
}
