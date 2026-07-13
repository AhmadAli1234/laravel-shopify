<?php

namespace App\Jobs\Shopify;

use App\Models\Collection;
use App\Models\Product;
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
 * Full paginated GraphQL backfill of a single shop's collections, including
 * product membership (webhooks only cover the collection's own attributes,
 * not membership, so this is the source of truth for that).
 */
class BackfillShopCollectionsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const QUERY = <<<'GRAPHQL'
        query Collections($cursor: String) {
            collections(first: 50, after: $cursor) {
                edges {
                    cursor
                    node {
                        legacyResourceId
                        title
                        handle
                        sortOrder
                        updatedAt
                        productsCount {
                            count
                        }
                        products(first: 100) {
                            edges {
                                node {
                                    legacyResourceId
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
            SyncProgressTracker::markStepDone($this->shopId, 'collections');

            return;
        }

        SyncProgressTracker::markStepRunning($this->shopId, 'collections');

        try {
            $cursor = null;
            $hasNextPage = true;

            while ($hasNextPage) {
                $response = $shop->api()->graph(self::QUERY, ['cursor' => $cursor]);

                if ($response['errors']) {
                    $details = $response['errors'] === true ? $response['body'] : $response['errors'];

                    throw new RuntimeException(
                        'GraphQL error syncing collections for '.$shop->name.': '
                        .(is_string($details) ? $details : json_encode($details))
                    );
                }

                $connection = $response['body']['data']['collections'];

                foreach ($connection['edges'] ?? [] as $edge) {
                    $node = $edge['node'];

                    $collection = Collection::updateOrCreate(
                        [
                            'shop_id' => $shop->id,
                            'shopify_collection_id' => $node['legacyResourceId'],
                        ],
                        [
                            'title' => $node['title'] ?? null,
                            'handle' => $node['handle'] ?? null,
                            'sort_order' => $node['sortOrder'] ?? null,
                            'products_count' => $node['productsCount']['count'] ?? null,
                            'shopify_updated_at' => $node['updatedAt'] ?? null,
                            'synced_at' => now(),
                        ]
                    );

                    $productIds = Product::where('shop_id', $shop->id)
                        ->whereIn(
                            'shopify_product_id',
                            collect($node['products']['edges'] ?? [])->pluck('node.legacyResourceId')
                        )
                        ->pluck('id');

                    $collection->products()->sync($productIds);

                    SyncProgressTracker::incrementStepCount($this->shopId, 'collections');
                    $cursor = $edge['cursor'];
                }

                $hasNextPage = (bool) ($connection['pageInfo']['hasNextPage'] ?? false);
            }

            SyncProgressTracker::markStepDone($this->shopId, 'collections');
        } catch (Throwable $e) {
            SyncProgressTracker::markStepFailed($this->shopId, 'collections');

            throw $e;
        }
    }
}
