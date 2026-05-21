<?php

namespace App\Jobs\WooCommerce;

use App\Services\WooCommerceService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

/**
 * Fetches a product (or variation) from WooCommerce and upserts it locally.
 *
 * Dispatched from the product.updated webhook and recursively from the
 * service itself when a variable product fans out to its variations.
 */
class SyncProductJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 30;

    public function __construct(
        public readonly int $wcId,
        public readonly ?int $wcParentId = null,
    ) {}

    public function handle(WooCommerceService $woocommerce): void
    {
        $woocommerce->syncProduct($this->wcId, $this->wcParentId);
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [(new WithoutOverlapping("wc-product:{$this->wcId}"))->releaseAfter(10)->expireAfter(120)];
    }
}
