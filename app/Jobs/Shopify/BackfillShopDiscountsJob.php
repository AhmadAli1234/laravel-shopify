<?php

namespace App\Jobs\Shopify;

use App\Models\Discount;
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
 * Full paginated GraphQL backfill of a single shop's discounts.
 *
 * Shopify's Discount type is a union of 8 concrete types (code/automatic x
 * basic/bxgy/free-shipping/app). Only the two most common - DiscountCodeBasic
 * and DiscountAutomaticBasic - are fully captured here via inline fragments;
 * the others (BOGO, free-shipping, app-based discounts) still get a row with
 * just their type name, since they don't match either fragment.
 */
class BackfillShopDiscountsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const QUERY = <<<'GRAPHQL'
        query Discounts($cursor: String) {
            discountNodes(first: 50, after: $cursor) {
                edges {
                    cursor
                    node {
                        id
                        discount {
                            __typename
                            ... on DiscountCodeBasic {
                                title
                                status
                                summary
                                startsAt
                                endsAt
                                updatedAt
                                codes(first: 1) {
                                    edges {
                                        node {
                                            code
                                        }
                                    }
                                }
                            }
                            ... on DiscountAutomaticBasic {
                                title
                                status
                                summary
                                startsAt
                                endsAt
                                updatedAt
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
            SyncProgressTracker::markStepDone($this->shopId, 'discounts');

            return;
        }

        SyncProgressTracker::markStepRunning($this->shopId, 'discounts');

        try {
            $cursor = null;
            $hasNextPage = true;

            while ($hasNextPage) {
                $response = $shop->api()->graph(self::QUERY, ['cursor' => $cursor]);

                if ($response['errors']) {
                    $details = $response['errors'] === true ? $response['body'] : $response['errors'];

                    throw new RuntimeException(
                        'GraphQL error syncing discounts for '.$shop->name.': '
                        .(is_string($details) ? $details : json_encode($details))
                    );
                }

                $connection = $response['body']['data']['discountNodes'];

                foreach ($connection['edges'] ?? [] as $edge) {
                    $node = $edge['node'];
                    $discount = $node['discount'];

                    Discount::updateOrCreate(
                        [
                            'shop_id' => $shop->id,
                            'shopify_discount_gid' => $node['id'],
                        ],
                        [
                            'discount_type' => $discount['__typename'] ?? null,
                            'title' => $discount['title'] ?? null,
                            'code' => $discount['codes']['edges'][0]['node']['code'] ?? null,
                            'status' => strtolower($discount['status'] ?? '') ?: null,
                            'summary' => $discount['summary'] ?? null,
                            'starts_at' => $discount['startsAt'] ?? null,
                            'ends_at' => $discount['endsAt'] ?? null,
                            'shopify_updated_at' => $discount['updatedAt'] ?? null,
                            'synced_at' => now(),
                        ]
                    );

                    SyncProgressTracker::incrementStepCount($this->shopId, 'discounts');
                    $cursor = $edge['cursor'];
                }

                $hasNextPage = (bool) ($connection['pageInfo']['hasNextPage'] ?? false);
            }

            SyncProgressTracker::markStepDone($this->shopId, 'discounts');
        } catch (Throwable $e) {
            SyncProgressTracker::markStepFailed($this->shopId, 'discounts');

            throw $e;
        }
    }
}
