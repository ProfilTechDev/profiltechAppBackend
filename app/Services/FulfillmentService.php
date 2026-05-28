<?php

namespace App\Services;

use App\Enums\DeliveryFlow;
use App\Enums\PackingStatus;
use App\Events\Fulfillment\OrderFlowChanged;
use App\Events\Fulfillment\OrderPackingReady;
use App\Events\Fulfillment\OrderPackingStatusChanged;
use App\Models\Order;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Domain logic for the planning/fulfilment layer: assigning an order's
 * delivery flow, gating its visibility to the warehouse floor via
 * `packing_ready_at`, and tracking where it is in the packing pipeline
 * via `packing_status`.
 *
 * Tour cleanup is delegated to TourService — when an order leaves the
 * Delivery flow it must come off any tour it was on, since tours are
 * Delivery-only by design.
 */
class FulfillmentService
{
    public function __construct(
        private readonly TourService $tours,
    ) {}

    /**
     * Change (or clear) an order's delivery flow. When the flow changes
     * the order's packing_status is reset to the new flow's default
     * (e.g. Delivery → NotPlanned, Shipping/Pickup → Packing, null →
     * null). If the flow leaves Delivery, the order is also removed
     * from its current tour in the same transaction. No-op if the flow
     * is unchanged.
     */
    public function setDeliveryFlow(Order $order, ?DeliveryFlow $flow): void
    {
        if ($order->delivery_flow === $flow) {
            return;
        }

        $previousFlow = $order->delivery_flow;

        DB::transaction(function () use ($order, $flow, $previousFlow): void {
            if ($previousFlow === DeliveryFlow::Delivery && $flow !== DeliveryFlow::Delivery) {
                $tour = $order->tours()->first();

                if ($tour !== null) {
                    $this->tours->removeOrderFromTour($tour, $order);
                }
            }

            $order->update([
                'delivery_flow' => $flow,
                'packing_status' => PackingStatus::defaultFor($flow),
            ]);
        });

        OrderFlowChanged::dispatch($order, $previousFlow);
    }

    /**
     * Update an order's packing_status. The status must be allowed for
     * the order's current flow (see PackingStatus::allowedFor). Pass
     * null only when the order has no flow yet.
     */
    public function setPackingStatus(Order $order, ?PackingStatus $status): void
    {
        if ($order->packing_status === $status) {
            return;
        }

        if ($status !== null && ! $status->isAllowedFor($order->delivery_flow, $order->tours()->exists())) {
            throw ValidationException::withMessages([
                'packing_status' => "Status {$status->value} is not allowed for flow ".($order->delivery_flow?->value ?? 'null').' in its current state.',
            ]);
        }

        $previous = $order->packing_status;

        $order->update(['packing_status' => $status]);

        OrderPackingStatusChanged::dispatch($order, $previous);
    }

    /**
     * Mark an order as ready to be packed — surfaces it in the planning
     * list. Used by the WC sync (auto-set for non-custom orders) and
     * by the custom-orders listener once the vendor-submission is
     * marked received. Idempotent: a second call is a no-op.
     */
    public function markPackingReady(Order $order): void
    {
        if ($order->packing_ready_at !== null) {
            return;
        }

        $order->update(['packing_ready_at' => now()]);

        OrderPackingReady::dispatch($order);
    }

    // ---------------------------------------------------------------
    // Backfill helpers — used by `backfill:*` artisan commands.
    //
    // These exist so backfill commands don't reach past the service
    // and mutate orders directly. They share the column-write code
    // with the domain transitions above but deliberately skip event
    // dispatch: a one-off historical fix should not flood the
    // fulfillment broadcast channel or trigger listeners that assume
    // a real domain transition.
    // ---------------------------------------------------------------

    /**
     * Backfill `delivery_flow` on a row that has none. Skips event
     * dispatch — see header above. No-op when the column is already
     * set so re-runs don't overwrite admin choices.
     */
    public function backfillDeliveryFlow(Order $order, DeliveryFlow $flow): void
    {
        if ($order->delivery_flow !== null) {
            return;
        }

        $order->update(['delivery_flow' => $flow]);
    }

    /**
     * Backfill `packing_status` on a row that has none. Skips event
     * dispatch. No-op when the column is already set.
     */
    public function backfillPackingStatus(Order $order, PackingStatus $status): void
    {
        if ($order->packing_status !== null) {
            return;
        }

        $order->update(['packing_status' => $status]);
    }

    /**
     * Backfill `packing_ready_at` to a specific historical timestamp
     * (typically `date_paid` or `date_created` from the WC payload).
     * Skips event dispatch. No-op when the column is already set.
     */
    public function backfillPackingReadyAt(Order $order, CarbonImmutable $at): void
    {
        if ($order->packing_ready_at !== null) {
            return;
        }

        $order->update(['packing_ready_at' => $at]);
    }
}
