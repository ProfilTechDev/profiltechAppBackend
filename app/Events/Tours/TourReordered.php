<?php

namespace App\Events\Tours;

use App\Models\Tour;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when a draft tour's sequence is renumbered. The payload
 * deliberately doesn't include the new ordering — clients should
 * refetch the tour to get a consistent snapshot rather than try to
 * reconcile a partial diff.
 */
class TourReordered implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Tour $tour,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel("tours.{$this->tour->id}")];
    }

    public function broadcastAs(): string
    {
        return 'Tours\\TourReordered';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'tour_id' => $this->tour->id,
        ];
    }
}
