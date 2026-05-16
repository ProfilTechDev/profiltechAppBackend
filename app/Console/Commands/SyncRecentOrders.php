<?php

namespace App\Console\Commands;

use App\Jobs\SyncWooCommerceOrderJob;
use App\Services\WooCommerce\WooCommerceService;
use App\Support\WooCommerce\WooCommerceClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Failsafe pull of recently modified WooCommerce orders.
 *
 * Runs nightly to catch any orders the webhook may have missed (e.g. due
 * to network errors or downtime). Pre-warms the local products table so
 * the dispatched sync jobs avoid hitting WooCommerce for product data.
 */
#[Signature('orders:sync-recent {--days=30}')]
#[Description('Pull WooCommerce orders modified within the last N days and dispatch sync jobs.')]
class SyncRecentOrders extends Command
{
    public function handle(WooCommerceClient $client, WooCommerceService $woocommerce): int
    {
        $days = (int) $this->option('days');
        $modifiedAfter = now()->subDays($days)->toIso8601String();

        $this->info("Fetching WooCommerce orders modified after {$modifiedAfter}...");

        $orders = $client->getOrdersModifiedAfter($modifiedAfter);

        $this->info("Pre-warming products from {$orders->count()} order(s)...");
        $fetched = $woocommerce->prefetchUnknownProducts($orders);
        $this->info("Fetched {$fetched} new product(s) from WooCommerce.");

        $orders->each(SyncWooCommerceOrderJob::dispatch(...));

        $this->info("Dispatched {$orders->count()} order sync job(s).");

        return self::SUCCESS;
    }
}
