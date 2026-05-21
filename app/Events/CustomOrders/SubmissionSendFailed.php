<?php

namespace App\Events\CustomOrders;

use App\Models\OrderSubmission;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Throwable;

/**
 * Lifecycle event fired by `CustomOrderService::deliverSubmission()`
 * when a single send attempt fails — *before* the exception is
 * re-thrown so the caller (queue/controller/CLI) can react. Distinct
 * from `SubmissionStatusUpdated` with status=Failed, which only fires
 * once the queue has exhausted retries and given up permanently.
 *
 * Listeners typically:
 *   - log a warning per attempt (already wired)
 *   - notify operations after N consecutive failures (future)
 *   - feed an alerting system (future)
 *
 * `$attempt` + `$willRetry` are non-null only when the delivery was
 * triggered by the queued job — manual resends pass null because
 * those concepts don't apply outside the queue.
 */
class SubmissionSendFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly OrderSubmission $submission,
        public readonly Throwable $exception,
        public readonly ?int $attempt = null,
        public readonly ?bool $willRetry = null,
    ) {}
}
