<?php

namespace App\Events\CustomOrders;

use App\Enums\SubmissionStatus;
use App\Models\OrderSubmission;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast on every transition of an OrderSubmission's status so the
 * frontend can reflect Sent / Failed without a manual refresh.
 *
 * Listened to on the private channel `orders.{orderId}.submission`.
 * The event name is explicitly set so frontend can listen on the
 * short form `.CustomOrders\SubmissionStatusUpdated` (with leading
 * dot to take the name verbatim).
 *
 * Uses `ShouldBroadcastNow` (sync broadcast) because we're already
 * inside a queued job — re-queueing the broadcast through Horizon
 * just adds latency and another queue to monitor.
 */
class SubmissionStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly OrderSubmission $submission,
        public readonly ?SubmissionStatus $previousStatus = null,
        public readonly ?string $reason = null,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("orders.{$this->submission->order_id}.submission"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'CustomOrders\\SubmissionStatusUpdated';
    }

    /**
     * Lean payload — frontend only needs the new status and sent_at.
     * Anything heavier can be re-fetched via the submission endpoint.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->submission->order_id,
            'status' => $this->submission->status?->value,
            'sent_at' => $this->submission->sent_at?->toIso8601String(),
        ];
    }
}
