<?php

namespace App\Console\Commands;

use App\Enums\PackingStatus;
use App\Models\Order;
use App\Services\FulfillmentService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * One-off (idempotent) backfill of Order.packing_status. Only touches
 * rows that have a delivery_flow but no packing_status yet — applies
 * the flow's default (Delivery → NotPlanned, Shipping/Pickup → Packing).
 *
 * Routes the mutation through `FulfillmentService::backfillPackingStatus`
 * to keep order-column writes centralised; the helper deliberately
 * skips event dispatch so this historical fix doesn't flood the
 * fulfillment broadcast channel.
 */
#[Signature('backfill:packing-status')]
#[Description('Populate Order.packing_status with the per-flow default for existing rows.')]
class BackfillPackingStatus extends Command
{
    public function handle(FulfillmentService $fulfillment): int
    {
        $updated = 0;

        Order::query()
            ->whereNotNull('delivery_flow')
            ->whereNull('packing_status')
            ->chunkById(200, function ($orders) use ($fulfillment, &$updated): void {
                foreach ($orders as $order) {
                    $default = PackingStatus::defaultFor($order->delivery_flow);

                    if ($default !== null) {
                        $fulfillment->backfillPackingStatus($order, $default);
                        $updated++;
                    }
                }
            });

        $this->info("Set packing_status on {$updated} order(s).");

        return self::SUCCESS;
    }
}
