<?php

namespace App\Events\CustomOrders;

use App\Models\OrderSubmission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Lifecycle event fired by `SendSubmissionJob` after mail delivery
 * succeeded and the submission was transitioned to Sent. Distinct
 * from `SubmissionStatusUpdated` so listeners can target the "send
 * succeeded" moment without needing to inspect status enums.
 */
class AfterSuccessfulSubmissionSend
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly OrderSubmission $submission,
    ) {}
}
