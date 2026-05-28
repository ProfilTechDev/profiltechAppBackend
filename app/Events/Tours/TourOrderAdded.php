<?php

namespace App\Events\Tours;

use App\Models\Tour;
use App\Models\TourOrder;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when an order is placed on a tour. Frontend tour-detail view
 * uses this to refresh the sequence list without polling.
 */
class TourOrderAdded implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Tour $tour,
        public readonly TourOrder $tourOrder,
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
        return 'Tours\\TourOrderAdded';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'tour_id' => $this->tour->id,
            'order_id' => $this->tourOrder->order_id,
            'primary_sequence' => $this->tourOrder->primary_sequence,
            'insert_index' => $this->tourOrder->insert_index,
            'display_sequence' => $this->tourOrder->display_sequence,
            'added_after_approval' => $this->tourOrder->added_after_approval,
        ];
    }
}
