<?php

namespace App\Jobs\Shopify;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;

/**
 * Handles the customers/create and customers/update webhooks.
 */
class SyncCustomerJob implements ShouldQueue
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

        Customer::updateOrCreate(
            [
                'shop_id' => $shop->id,
                'shopify_customer_id' => $this->data->id,
            ],
            [
                'first_name' => $this->data->first_name ?? null,
                'last_name' => $this->data->last_name ?? null,
                'email' => $this->data->email ?? null,
                'phone' => $this->data->phone ?? null,
                'state' => $this->data->state ?? null,
                'orders_count' => $this->data->orders_count ?? null,
                'total_spent' => $this->data->total_spent ?? null,
                'note' => $this->data->note ?? null,
                'shopify_updated_at' => $this->data->updated_at ?? null,
                'synced_at' => now(),
            ]
        );
    }
}
