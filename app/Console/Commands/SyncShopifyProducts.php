<?php

namespace App\Console\Commands;

use App\Jobs\Shopify\BackfillShopProductsJob;
use App\Models\User;
use Illuminate\Console\Command;
use Throwable;

/**
 * Backfills products/variants from Shopify via GraphQL into the local cache
 * tables. Webhooks (products-create/update/delete, inventory-levels-update)
 * keep them in sync going forward, but only cover changes from this point on.
 * New shops get this automatically on install (see BackfillProductsOnInstall);
 * this command is for manual resyncs and the nightly reconciliation cron.
 */
class SyncShopifyProducts extends Command
{
    protected $signature = 'shopify:sync-products {shop? : Shop domain to sync (all installed shops if omitted)}';

    protected $description = 'Backfill products and variants from Shopify into the local cache tables';

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
            $this->info("Syncing products for {$shop->name}...");

            try {
                BackfillShopProductsJob::dispatchSync($shop->id);
                $this->info("Done: {$shop->name}.");
            } catch (Throwable $e) {
                $this->error("Failed for {$shop->name}: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
