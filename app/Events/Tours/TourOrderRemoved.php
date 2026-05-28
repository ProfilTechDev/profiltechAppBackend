<?php

namespace App\Events\Tours;

use App\Models\Order;
use App\Models\Tour;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when an order is taken off a tour. Sequence numbers are NOT
 * renumbered (paper labels stay valid) so the resulting gap is
 * intentional and clients should render it as such.
 */
class TourOrderRemoved implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Tour $tour,
        public readonly Order $order,
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
        return 'Tours\\TourOrderRemoved';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'tour_id' => $this->tour->id,
            'order_id' => $this->order->id,
        ];
    }
}
