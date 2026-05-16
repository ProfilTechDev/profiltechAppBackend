<?php

namespace App\Jobs;

use App\Data\WooCommerce\OrderData;
use App\Services\WooCommerce\WooCommerceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

/**
 * Persists a single WooCommerce order payload via WooCommerceService.
 *
 * Dispatched from the order.updated webhook and from the nightly
 * recent-orders sync command.
 */
class SyncWooCommerceOrderJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(public readonly OrderData $order) {}

    public function handle(WooCommerceService $woocommerce): void
    {
        $woocommerce->syncOrder($this->order);
    }

    /**
     * Prevent two workers from syncing the same WooCommerce order at the
     * same time. Overlapping jobs are released back to the queue.
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("wc-order:{$this->order->id}"))->releaseAfter(10)->expireAfter(120)];
    }
}
