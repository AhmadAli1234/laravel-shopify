<?php

namespace App\Console\Commands;

use App\Jobs\Shopify\BackfillShopCollectionsJob;
use App\Jobs\Shopify\BackfillShopCustomersJob;
use App\Jobs\Shopify\BackfillShopDiscountsJob;
use App\Jobs\Shopify\BackfillShopOrdersJob;
use App\Jobs\Shopify\BackfillShopProductsJob;
use App\Models\User;
use Illuminate\Console\Command;
use Throwable;

/**
 * Full reconciliation sync across every synced entity (products, customers,
 * collections, orders, discounts). New stores get this automatically on
 * install (see BackfillShopDataOnInstall); this command is for manual runs
 * and the nightly cron safety net, since webhook delivery isn't guaranteed.
 */
class SyncShopifyData extends Command
{
    protected $signature = 'shopify:sync {shop? : Shop domain to sync (all installed shops if omitted)}';

    protected $description = 'Full reconciliation sync of all Shopify data into the local cache tables';

    private const JOBS = [
        'products' => BackfillShopProductsJob::class,
        'customers' => BackfillShopCustomersJob::class,
        'collections' => BackfillShopCollectionsJob::class,
        'orders' => BackfillShopOrdersJob::class,
        'discounts' => BackfillShopDiscountsJob::class,
    ];

    public function handle(): int
    {
        $shops = $this->argument('shop')
            ? User::where('name', $this->argument('shop'))->get()
            : User::whereNotNull('password')->where('password', '!=', '')->get();

        if ($shops->isEmpty()) {
            $this->error('No installed shop found.');

            return self::FAILURE;
        }

        foreach ($shops as $shop) {
            $this->info("Syncing {$shop->name}...");

            foreach (self::JOBS as $label => $jobClass) {
                try {
                    $jobClass::dispatchSync($shop->id);
                    $this->info("  {$label}: done");
                } catch (Throwable $e) {
                    $this->error("  {$label}: failed - {$e->getMessage()}");
                }
            }
        }

        return self::SUCCESS;
    }
}
