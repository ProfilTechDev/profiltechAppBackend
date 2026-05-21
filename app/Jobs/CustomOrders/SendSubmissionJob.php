<?php

namespace App\Jobs\CustomOrders;

use App\Models\OrderSubmission;
use App\Services\CustomOrderService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Throwable;

/**
 * Queue adapter around `CustomOrderService::deliverSubmission()`.
 *
 * Holds queue-specific concerns the service does not know about:
 *   - retry policy (10 attempts, 10-minute backoff)
 *   - concurrency control (one in-flight send per submission)
 *   - permanent-failure callback (transition to Failed status after
 *     all retries are exhausted)
 *
 * The actual sending logic + lifecycle events live in the service so
 * other entry points (manual resend, CLI commands) can deliver
 * submissions without going through the queue.
 */
class SendSubmissionJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 10;

    public int $backoff = 600;

    public function __construct(public readonly OrderSubmission $submission) {}

    public function handle(CustomOrderService $service): void
    {
        $service->deliverSubmission(
            $this->submission,
            attempt: $this->attempts(),
            willRetry: $this->attempts() < $this->tries,
        );
    }

    public function failed(Throwable $exception): void
    {
        app(CustomOrderService::class)->markSubmissionFailed(
            $this->submission,
            $exception->getMessage(),
        );
    }

    /**
     * Prevent the same submission from being sent twice concurrently
     * (e.g. if a manual re-send is triggered while a queued attempt is
     * still pending).
     *
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping("submission:{$this->submission->id}"))
                ->releaseAfter(60)
                ->expireAfter(7200),
        ];
    }
}
