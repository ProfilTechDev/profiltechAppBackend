<?php

namespace App\Listeners\CustomOrders\SubmissionReceived;

use App\Events\CustomOrders\SubmissionReceived;
use App\Services\FulfillmentService;

/**
 * Bridges the custom-orders and fulfilment domains: once the vendor
 * submission's goods have arrived, the underlying order is allowed
 * into the planning bucket. FulfillmentService dispatches its own
 * broadcast event from there, so the planning UI updates in real time
 * without the custom-orders domain having to know about it.
 */
class MarkOrderPackingReady
{
    public function __construct(
        private readonly FulfillmentService $fulfillment,
    ) {}

    public function handle(SubmissionReceived $event): void
    {
        $event->submission->loadMissing('order');

        if ($event->submission->order === null) {
            return;
        }

        $this->fulfillment->markPackingReady($event->submission->order);
    }
}
