<?php

namespace App\Jobs\Shopify;

use App\Models\Customer;
use App\Models\Fulfillment;
use App\Models\Order;
use App\Models\OrderLineItem;
use App\Models\OrderTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\SyncProgressTracker;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Full paginated GraphQL backfill of a single shop's orders + line items +
 * fulfillments + transactions. The latter two have no dedicated top-level
 * GraphQL query (they're sub-resources of Order), so they're backfilled here
 * as nested fields rather than in separate jobs. Line items and transactions
 * also have no legacyResourceId in the GraphQL API, so their numeric id is
 * extracted from the end of their GID (gid://shopify/LineItem/123).
 */
class BackfillShopOrdersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const QUERY = <<<'GRAPHQL'
        query Orders($cursor: String) {
            orders(first: 25, after: $cursor) {
                edges {
                    cursor
                    node {
                        legacyResourceId
                        name
                        email
                        phone
                        currencyCode
                        totalPriceSet {
                            shopMoney {
                                amount
                            }
                        }
                        displayFinancialStatus
                        displayFulfillmentStatus
                        customer {
                            legacyResourceId
                        }
                        shippingAddress {
                            address1
                            address2
                            city
                            province
                            country
                            zip
                            phone
                            name
                            company
                        }
                        billingAddress {
                            address1
                            address2
                            city
                            province
                            country
                            zip
                            phone
                            name
                            company
                        }
                        processedAt
                        cancelledAt
                        closedAt
                        updatedAt
                        lineItems(first: 100) {
                            edges {
                                node {
                                    id
                                    title
                                    sku
                                    quantity
                                    originalUnitPriceSet {
                                        shopMoney {
                                            amount
                                        }
                                    }
                                    product {
                                        legacyResourceId
                                    }
                                    variant {
                                        legacyResourceId
                                    }
                                }
                            }
                        }
                        fulfillments(first: 10) {
                            legacyResourceId
                            status
                            updatedAt
                            trackingInfo {
                                company
                                number
                                url
                            }
                        }
                        transactions(first: 10) {
                            id
                            kind
                            status
                            gateway
                            processedAt
                            amountSet {
                                shopMoney {
                                    amount
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
            SyncProgressTracker::markStepDone($this->shopId, 'orders');

            return;
        }

        SyncProgressTracker::markStepRunning($this->shopId, 'orders');

        try {
            $this->sync($shop);
            SyncProgressTracker::markStepDone($this->shopId, 'orders');
        } catch (Throwable $e) {
            SyncProgressTracker::markStepFailed($this->shopId, 'orders');

            throw $e;
        }
    }

    private function sync(User $shop): void
    {
        $cursor = null;
        $hasNextPage = true;

        while ($hasNextPage) {
            $response = $shop->api()->graph(self::QUERY, ['cursor' => $cursor]);

            if ($response['errors']) {
                $details = $response['errors'] === true ? $response['body'] : $response['errors'];

                throw new RuntimeException(
                    'GraphQL error syncing orders for '.$shop->name.': '
                    .(is_string($details) ? $details : json_encode($details))
                );
            }

            $connection = $response['body']['data']['orders'];

            foreach ($connection['edges'] ?? [] as $edge) {
                $node = $edge['node'];

                $customerId = isset($node['customer']['legacyResourceId'])
                    ? Customer::where('shop_id', $shop->id)
                        ->where('shopify_customer_id', $node['customer']['legacyResourceId'])
                        ->value('id')
                    : null;

                $order = Order::updateOrCreate(
                    [
                        'shop_id' => $shop->id,
                        'shopify_order_id' => $node['legacyResourceId'],
                    ],
                    [
                        'customer_id' => $customerId,
                        'name' => $node['name'] ?? null,
                        'email' => $node['email'] ?? null,
                        'phone' => $node['phone'] ?? null,
                        'currency' => $node['currencyCode'] ?? null,
                        'total_price' => $node['totalPriceSet']['shopMoney']['amount'] ?? null,
                        'financial_status' => strtolower($node['displayFinancialStatus'] ?? '') ?: null,
                        'fulfillment_status' => strtolower($node['displayFulfillmentStatus'] ?? '') ?: null,
                        'shipping_address' => $node['shippingAddress'] ?? null,
                        'billing_address' => $node['billingAddress'] ?? null,
                        'processed_at' => $node['processedAt'] ?? null,
                        'cancelled_at' => $node['cancelledAt'] ?? null,
                        'closed_at' => $node['closedAt'] ?? null,
                        'shopify_updated_at' => $node['updatedAt'] ?? null,
                        'synced_at' => now(),
                    ]
                );

                foreach ($node['lineItems']['edges'] ?? [] as $lineItemEdge) {
                    $lineItemNode = $lineItemEdge['node'];
                    $rawGid = $lineItemNode['id'];
                    $lineItemId = Str::afterLast($rawGid, '/');

                    $productId = isset($lineItemNode['product']['legacyResourceId'])
                        ? Product::where('shop_id', $shop->id)
                            ->where('shopify_product_id', $lineItemNode['product']['legacyResourceId'])
                            ->value('id')
                        : null;

                    $variantId = isset($lineItemNode['variant']['legacyResourceId'])
                        ? ProductVariant::where('shopify_variant_id', $lineItemNode['variant']['legacyResourceId'])
                            ->value('id')
                        : null;

                    try {
                        OrderLineItem::updateOrCreate(
                            ['order_id' => $order->id, 'shopify_line_item_id' => $lineItemId],
                            [
                                'product_id' => $productId,
                                'product_variant_id' => $variantId,
                                'title' => $lineItemNode['title'] ?? null,
                                'sku' => $lineItemNode['sku'] ?? null,
                                'quantity' => $lineItemNode['quantity'] ?? null,
                                'price' => $lineItemNode['originalUnitPriceSet']['shopMoney']['amount'] ?? null,
                            ]
                        );
                    } catch (\Throwable $e) {
                        // Debug context for "Out of range value for column
                        // shopify_line_item_id" type errors - shows exactly
                        // what Shopify sent, what PHP computed from it, and
                        // this environment's actual live column definition,
                        // so a schema/environment mismatch is visible
                        // directly in the log instead of guessing blind.
                        Log::error('Failed to save order line item - debug context', [
                            'shop' => $shop->name,
                            'order_shopify_id' => $node['legacyResourceId'] ?? null,
                            'raw_line_item_gid' => $rawGid,
                            'computed_line_item_id' => $lineItemId,
                            'computed_line_item_id_type' => gettype($lineItemId),
                            'php_int_max' => PHP_INT_MAX,
                            'php_int_size_bits' => PHP_INT_SIZE * 8,
                            'live_column_definition' => collect(
                                \Illuminate\Support\Facades\DB::select(
                                    "SHOW FULL COLUMNS FROM order_line_items WHERE Field = 'shopify_line_item_id'"
                                )
                            )->first(),
                            'exception' => $e->getMessage(),
                        ]);

                        throw $e;
                    }
                }

                foreach ($node['fulfillments'] ?? [] as $fulfillmentNode) {
                    $trackingInfo = $fulfillmentNode['trackingInfo'][0] ?? [];

                    Fulfillment::updateOrCreate(
                        [
                            'order_id' => $order->id,
                            'shopify_fulfillment_id' => $fulfillmentNode['legacyResourceId'],
                        ],
                        [
                            'status' => strtolower($fulfillmentNode['status'] ?? '') ?: null,
                            'tracking_company' => $trackingInfo['company'] ?? null,
                            'tracking_number' => $trackingInfo['number'] ?? null,
                            'tracking_url' => $trackingInfo['url'] ?? null,
                            'shopify_updated_at' => $fulfillmentNode['updatedAt'] ?? null,
                            'synced_at' => now(),
                        ]
                    );
                }

                foreach ($node['transactions'] ?? [] as $transactionNode) {
                    $transactionId = (int) Str::afterLast($transactionNode['id'], '/');

                    OrderTransaction::updateOrCreate(
                        [
                            'order_id' => $order->id,
                            'shopify_transaction_id' => $transactionId,
                        ],
                        [
                            'kind' => strtolower($transactionNode['kind'] ?? '') ?: null,
                            'status' => strtolower($transactionNode['status'] ?? '') ?: null,
                            'gateway' => $transactionNode['gateway'] ?? null,
                            'amount' => $transactionNode['amountSet']['shopMoney']['amount'] ?? null,
                            'processed_at' => $transactionNode['processedAt'] ?? null,
                            'synced_at' => now(),
                        ]
                    );
                }

                SyncProgressTracker::incrementStepCount($this->shopId, 'orders');
                $cursor = $edge['cursor'];
            }

            $hasNextPage = (bool) ($connection['pageInfo']['hasNextPage'] ?? false);
        }
    }
}
