<?php

namespace App\Events\Tours;

use App\Enums\TourStatus;
use App\Models\Tour;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast on every Tour status transition so multi-admin planning
 * sessions stay in sync (approving a tour on one screen instantly
 * locks the sequence on another). Frontend listens on the short form
 * `.Tours\TourStatusUpdated`.
 */
class TourStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Tour $tour,
        public readonly ?TourStatus $previousStatus = null,
        public readonly ?string $reason = null,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("tours.{$this->tour->id}"),
            new PrivateChannel('tours'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'Tours\\TourStatusUpdated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'tour_id' => $this->tour->id,
            'status' => $this->tour->status?->value,
            'approved_at' => $this->tour->approved_at?->toIso8601String(),
            'completed_at' => $this->tour->completed_at?->toIso8601String(),
        ];
    }
}
