<?php

namespace App\Events\CustomOrders;

use App\Models\OrderSubmission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Lifecycle event fired by `CustomOrderService::deliverSubmission()`
 * immediately before mail delivery is attempted. Not a state
 * transition — status stays Queued (or whatever it was).
 *
 * `$attempt` is non-null when the call originated from the queued
 * `SendSubmissionJob` (carries the retry counter); null when triggered
 * out-of-band (e.g. manual resend from a controller).
 */
class BeforeSubmissionSend
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly OrderSubmission $submission,
        public readonly ?int $attempt = null,
    ) {}
}
