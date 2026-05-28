<?php

namespace App\Events\CustomOrders;

use App\Models\OrderSubmission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when admin marks a vendor submission as received — the
 * physical goods have arrived at the warehouse and the order can now
 * enter the packing flow.
 *
 * Listeners react by stamping `packing_ready_at` on the parent order
 * (via FulfillmentService::markPackingReady) which in turn broadcasts
 * to the planning UI. We do NOT make this event broadcast directly —
 * keeping the broadcast on `OrderPackingReady` ensures there's one
 * source of truth for "this order is now in the planning bucket".
 */
class SubmissionReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly OrderSubmission $submission,
    ) {}
}
