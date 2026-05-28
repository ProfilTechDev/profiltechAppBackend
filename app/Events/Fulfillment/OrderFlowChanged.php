<?php

namespace App\Events\Fulfillment;

use App\Enums\DeliveryFlow;
use App\Models\Order;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Fired when an order's delivery_flow changes. Broadcast on the shared
 * `fulfillment` channel so any open planning view (lists or pickers)
 * can refresh its bucket counts and per-flow rows.
 */
class OrderFlowChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Order $order,
        public readonly ?DeliveryFlow $previousFlow = null,
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
        return 'Fulfillment\\OrderFlowChanged';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'order_id' => $this->order->id,
            'delivery_flow' => $this->order->delivery_flow?->value,
            'previous_flow' => $this->previousFlow?->value,
        ];
    }
}
