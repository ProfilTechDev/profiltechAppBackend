<?php

namespace App\Console\Commands;

use App\Enums\DeliveryFlow;
use App\Models\Order;
use App\Services\FulfillmentService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * One-off (idempotent) backfill of the two planning columns on the
 * orders table.
 *
 *   - delivery_flow:    derived from the order's shipment category for
 *                       rows that don't already have a value.
 *   - packing_ready_at: set to date_paid (or date_created as fallback)
 *                       for non-custom orders that don't already have a
 *                       value. Custom orders stay NULL until admin
 *                       marks the corresponding OrderSubmission as
 *                       received from the vendor.
 *
 * Mutations route through `FulfillmentService::backfill*` so the
 * column-write code stays centralised; the helpers deliberately skip
 * event dispatch so a one-off historical fix doesn't flood the
 * fulfillment broadcast channel.
 */
#[Signature('backfill:order-planning')]
#[Description('Populate Order.delivery_flow + Order.packing_ready_at for existing orders.')]
class BackfillOrderPlanning extends Command
{
    public function handle(FulfillmentService $fulfillment): int
    {
        $flowUpdated = 0;

        Order::query()
            ->whereNull('delivery_flow')
            ->with('shipment')
            ->chunkById(200, function ($orders) use ($fulfillment, &$flowUpdated): void {
                foreach ($orders as $order) {
                    $derived = DeliveryFlow::fromShippingCategory($order->shipment?->category);

                    if ($derived !== null) {
                        $fulfillment->backfillDeliveryFlow($order, $derived);
                        $flowUpdated++;
                    }
                }
            });

        $this->info("Set delivery_flow on {$flowUpdated} order(s).");

        $readyUpdated = 0;

        Order::query()
            ->whereNull('packing_ready_at')
            ->whereDoesntHave('lines.snapshot', fn ($q) => $q->where('is_custom', true))
            ->chunkById(200, function ($orders) use ($fulfillment, &$readyUpdated): void {
                foreach ($orders as $order) {
                    $at = $order->date_paid ?? $order->date_created ?? CarbonImmutable::now();

                    $fulfillment->backfillPackingReadyAt($order, $at);
                    $readyUpdated++;
                }
            });

        $this->info("Set packing_ready_at on {$readyUpdated} non-custom order(s).");

        return self::SUCCESS;
    }
}
