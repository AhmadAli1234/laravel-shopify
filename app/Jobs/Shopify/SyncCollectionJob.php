<?php

namespace App\Jobs\Shopify;

use App\Models\Collection;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Osiset\ShopifyApp\Objects\Values\ShopDomain;

/**
 * Handles the collections/create and collections/update webhooks.
 * Note: these webhooks carry the collection's own attributes only, not its
 * product membership - that's kept current via the GraphQL backfill instead.
 */
class SyncCollectionJob implements ShouldQueue
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

        Collection::updateOrCreate(
            [
                'shop_id' => $shop->id,
                'shopify_collection_id' => $this->data->id,
            ],
            [
                'title' => $this->data->title ?? null,
                'handle' => $this->data->handle ?? null,
                'sort_order' => $this->data->sort_order ?? null,
                'shopify_updated_at' => $this->data->updated_at ?? null,
                'synced_at' => now(),
            ]
        );
    }
}
