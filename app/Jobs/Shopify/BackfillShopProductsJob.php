<?php

namespace App\Jobs\Shopify;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\SyncProgressTracker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RuntimeException;
use Throwable;

/**
 * Full paginated GraphQL backfill of a single shop's products/variants into
 * the local cache tables. Used both for the initial sync right after a shop
 * installs (dispatched from BackfillProductsOnInstall) and for manual/cron
 * reconciliation (see shopify:sync-products).
 */
class BackfillShopProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const QUERY = <<<'GRAPHQL'
        query Products($cursor: String) {
            products(first: 50, after: $cursor) {
                edges {
                    cursor
                    node {
                        legacyResourceId
                        title
                        handle
                        vendor
                        productType
                        status
                        updatedAt
                        featuredImage {
                            url
                        }
                        variants(first: 100) {
                            edges {
                                node {
                                    legacyResourceId
                                    sku
                                    price
                                    inventoryQuantity
                                    inventoryItem {
                                        legacyResourceId
                                    }
                                }
                            }
                        }
                    }
                }
                pageInfo {
                    hasNextPage
                }
            }
        }
        GRAPHQL;

    public function __construct(public int $shopId)
    {
    }

    public function handle(): void
    {
        $shop = User::find($this->shopId);

        if (! $shop || blank($shop->password)) {
            SyncProgressTracker::markStepDone($this->shopId, 'products');

            return;
        }

        SyncProgressTracker::markStepRunning($this->shopId, 'products');

        try {
            $cursor = null;
            $hasNextPage = true;

            while ($hasNextPage) {
                $response = $shop->api()->graph(self::QUERY, ['cursor' => $cursor]);

                if ($response['errors']) {
                    $details = $response['errors'] === true ? $response['body'] : $response['errors'];

                    throw new RuntimeException(
                        'GraphQL error syncing products for '.$shop->name.': '
                        .(is_string($details) ? $details : json_encode($details))
                    );
                }

                $connection = $response['body']['data']['products'];

                foreach ($connection['edges'] ?? [] as $edge) {
                    $node = $edge['node'];

                    $product = Product::updateOrCreate(
                        [
                            'shop_id' => $shop->id,
                            'shopify_product_id' => $node['legacyResourceId'],
                        ],
                        [
                            'title' => $node['title'] ?? null,
                            'handle' => $node['handle'] ?? null,
                            'vendor' => $node['vendor'] ?? null,
                            'product_type' => $node['productType'] ?? null,
                            'status' => strtolower($node['status'] ?? 'unknown'),
                            'image_url' => $node['featuredImage']['url'] ?? null,
                            'shopify_updated_at' => $node['updatedAt'] ?? null,
                            'synced_at' => now(),
                        ]
                    );

                    foreach ($node['variants']['edges'] ?? [] as $variantEdge) {
                        $variantNode = $variantEdge['node'];

                        ProductVariant::updateOrCreate(
                            ['shopify_variant_id' => $variantNode['legacyResourceId']],
                            [
                                'product_id' => $product->id,
                                'shopify_inventory_item_id' => $variantNode['inventoryItem']['legacyResourceId'] ?? null,
                                'sku' => $variantNode['sku'] ?? null,
                                'price' => $variantNode['price'] ?? null,
                                'inventory_quantity' => $variantNode['inventoryQuantity'] ?? null,
                                'synced_at' => now(),
                            ]
                        );
                    }

                    SyncProgressTracker::incrementStepCount($this->shopId, 'products');
                    $cursor = $edge['cursor'];
                }

                $hasNextPage = (bool) ($connection['pageInfo']['hasNextPage'] ?? false);
            }

            SyncProgressTracker::markStepDone($this->shopId, 'products');
        } catch (Throwable $e) {
            SyncProgressTracker::markStepFailed($this->shopId, 'products');

            throw $e;
        }
    }
}
