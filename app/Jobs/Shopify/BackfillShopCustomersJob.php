<?php

namespace App\Jobs\Shopify;

use App\Models\Customer;
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
 * Full paginated GraphQL backfill of a single shop's customers into the
 * local cache table. Mirrors BackfillShopProductsJob.
 */
class BackfillShopCustomersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const QUERY = <<<'GRAPHQL'
        query Customers($cursor: String) {
            customers(first: 50, after: $cursor) {
                edges {
                    cursor
                    node {
                        legacyResourceId
                        firstName
                        lastName
                        defaultEmailAddress {
                            emailAddress
                        }
                        defaultPhoneNumber {
                            phoneNumber
                        }
                        state
                        numberOfOrders
                        amountSpent {
                            amount
                        }
                        note
                        updatedAt
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
            SyncProgressTracker::markStepDone($this->shopId, 'customers');

            return;
        }

        SyncProgressTracker::markStepRunning($this->shopId, 'customers');

        try {
            $cursor = null;
            $hasNextPage = true;

            while ($hasNextPage) {
                $response = $shop->api()->graph(self::QUERY, ['cursor' => $cursor]);

                if ($response['errors']) {
                    $details = $response['errors'] === true ? $response['body'] : $response['errors'];

                    throw new RuntimeException(
                        'GraphQL error syncing customers for '.$shop->name.': '
                        .(is_string($details) ? $details : json_encode($details))
                    );
                }

                $connection = $response['body']['data']['customers'];

                foreach ($connection['edges'] ?? [] as $edge) {
                    $node = $edge['node'];

                    Customer::updateOrCreate(
                        [
                            'shop_id' => $shop->id,
                            'shopify_customer_id' => $node['legacyResourceId'],
                        ],
                        [
                            'first_name' => $node['firstName'] ?? null,
                            'last_name' => $node['lastName'] ?? null,
                            'email' => $node['defaultEmailAddress']['emailAddress'] ?? null,
                            'phone' => $node['defaultPhoneNumber']['phoneNumber'] ?? null,
                            'state' => $node['state'] ?? null,
                            'orders_count' => $node['numberOfOrders'] ?? null,
                            'total_spent' => $node['amountSpent']['amount'] ?? null,
                            'note' => $node['note'] ?? null,
                            'shopify_updated_at' => $node['updatedAt'] ?? null,
                            'synced_at' => now(),
                        ]
                    );

                    SyncProgressTracker::incrementStepCount($this->shopId, 'customers');
                    $cursor = $edge['cursor'];
                }

                $hasNextPage = (bool) ($connection['pageInfo']['hasNextPage'] ?? false);
            }

            SyncProgressTracker::markStepDone($this->shopId, 'customers');
        } catch (Throwable $e) {
            SyncProgressTracker::markStepFailed($this->shopId, 'customers');

            throw $e;
        }
    }
}
