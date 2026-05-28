<?php

namespace App\Events\Fulfillment;

use App\Enums\PackingStatus;
use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when an order's packing_status changes. Broadcast on the
 * shared `fulfillment` channel so open planning views refresh their
 * status badges and any status-filtered list.
 */
class OrderPackingStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly ?PackingStatus $previousStatus = null,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [new PrivateChannel('fulfillment')];
    }

    public function broadcastAs(): string
    {
        return 'Fulfillment\\OrderPackingStatusChanged';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'packing_status' => $this->order->packing_status?->value,
            'previous_status' => $this->previousStatus?->value,
        ];
    }
}
