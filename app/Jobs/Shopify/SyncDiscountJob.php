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
 * Handles the discounts/create and discounts/update webhooks.
 *
 * Unlike products/orders/customers, discounts have no REST-style legacy
 * resource - the webhook payload is a thin notification carrying only the
 * GraphQL global id, so this re-fetches full details via discountNode(id:)
 * rather than reading fields off the webhook payload directly.
 */
class SyncDiscountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const QUERY = <<<'GRAPHQL'
        query Discount($id: ID!) {
            discountNode(id: $id) {
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
        GRAPHQL;

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

        $response = $shop->api()->graph(self::QUERY, ['id' => $gid]);

        if ($response['errors'] || ! $response['body']['data']['discountNode']) {
            return;
        }

        $discount = $response['body']['data']['discountNode']['discount'];

        Discount::updateOrCreate(
            [
                'shop_id' => $shop->id,
                'shopify_discount_gid' => $gid,
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
    }
}
