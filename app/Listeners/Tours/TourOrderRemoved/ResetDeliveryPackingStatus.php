<?php

namespace App\Listeners\Tours\TourOrderRemoved;

use App\Enums\DeliveryFlow;
use App\Enums\PackingStatus;
use App\Events\Tours\TourOrderRemoved;
use App\Services\FulfillmentService;

/**
 * When a Delivery order is taken off its route while mid-pipeline
 * (Packing / Packed / OutForDelivery), reset its packing_status back
 * to NotPlanned — those states require the order to be on a route to
 * be valid, and leaving the status in place would be inconsistent
 * with the validation rules.
 *
 * NotPlanned and Done are left untouched: NotPlanned is already the
 * correct reset state, and Done is a deliberate end-state that admin
 * may have marked outside the route flow.
 */
class ResetDeliveryPackingStatus
{
    public function __construct(
        private readonly FulfillmentService $fulfillment,
    ) {}

    public function handle(TourOrderRemoved $event): void
    {
        $order = $event->order;

        if ($order->delivery_flow !== DeliveryFlow::Delivery) {
            return;
        }

        $needsReset = in_array($order->packing_status, [
            PackingStatus::Packing,
            PackingStatus::Packed,
            PackingStatus::OutForDelivery,
        ], true);

        if (! $needsReset) {
            return;
        }

        $this->fulfillment->setPackingStatus($order, PackingStatus::NotPlanned);
    }
}
