<?php

namespace App\Listeners\Tours\TourOrderAdded;

use App\Enums\PackingStatus;
use App\Enums\TourStatus;
use App\Events\Tours\TourOrderAdded;
use App\Services\FulfillmentService;

/**
 * When an order is placed on an *approved* route, automatically
 * transition its packing_status from NotPlanned → Packing — the route
 * is already on the floor and the new order joins the pipeline
 * immediately.
 *
 * Draft routes are skipped: pakning starts on approval (see the
 * companion listener on TourStatusUpdated), not when orders are still
 * being slotted together.
 *
 * Statuses other than NotPlanned are left alone: admin may have
 * deliberately marked the order Done, or re-added it mid-pipeline
 * from another tour where it was already further along.
 */
class AutoStartPacking
{
    public function __construct(
        private readonly FulfillmentService $fulfillment,
    ) {}

    public function handle(TourOrderAdded $event): void
    {
        if ($event->tour->status !== TourStatus::Approved) {
            return;
        }

        $order = $event->tourOrder->order;

        if ($order === null) {
            return;
        }

        if ($order->packing_status !== PackingStatus::NotPlanned) {
            return;
        }

        $this->fulfillment->setPackingStatus($order, PackingStatus::Packing);
    }
}
