<?php

namespace App\Listeners\Tours\TourStatusUpdated;

use App\Enums\PackingStatus;
use App\Enums\TourStatus;
use App\Events\Tours\TourStatusUpdated;
use App\Services\FulfillmentService;

/**
 * When a route is approved (Draft → Approved), kick all its orders
 * off in packing — i.e. transition each order's packing_status from
 * NotPlanned → Packing. The approval is the moment pakning starts on
 * the floor.
 *
 * Orders already past NotPlanned (Packed / OutForDelivery / Done) are
 * left alone — those are admin / floor states that the listener
 * shouldn't roll back.
 */
class StartPackingForOrders
{
    public function __construct(
        private readonly FulfillmentService $fulfillment,
    ) {}

    public function handle(TourStatusUpdated $event): void
    {
        if ($event->previousStatus !== TourStatus::Draft) {
            return;
        }

        if ($event->tour->status !== TourStatus::Approved) {
            return;
        }

        $event->tour->loadMissing('tourOrders.order');

        foreach ($event->tour->tourOrders as $slot) {
            $order = $slot->order;

            if ($order === null || $order->packing_status !== PackingStatus::NotPlanned) {
                continue;
            }

            $this->fulfillment->setPackingStatus($order, PackingStatus::Packing);
        }
    }
}
