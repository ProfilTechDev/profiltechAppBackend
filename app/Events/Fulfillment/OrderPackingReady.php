<?php

namespace App\Events\Fulfillment;

use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when an order becomes eligible for packing — typically a
 * non-custom order on first sync, or a custom order whose vendor
 * submission has just been marked received. The planning list uses
 * this to surface the row without polling.
 */
class OrderPackingReady implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Order $order,
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
        return 'Fulfillment\\OrderPackingReady';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'packing_ready_at' => $this->order->packing_ready_at?->toIso8601String(),
        ];
    }
}
