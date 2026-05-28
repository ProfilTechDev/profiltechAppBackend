<?php

namespace App\Http\Controllers;

use App\Data\Orders\OrderData;
use App\Enums\DeliveryFlow;
use App\Enums\PackingStatus;
use App\Http\Requests\Fulfillment\ListFulfillmentRequest;
use App\Http\Requests\Fulfillment\SetDeliveryFlowRequest;
use App\Http\Requests\Fulfillment\SetPackingStatusRequest;
use App\Models\Order;
use App\Queries\FulfillmentQuery;
use App\Services\FulfillmentService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Planning/fulfilment endpoints — the new business domain. Lists
 * orders that are ready to pack and lets admins move them between
 * flows (delivery / shipping / pickup) or back to the unassigned
 * bucket.
 */
class FulfillmentController
{
    public function __construct(
        private readonly FulfillmentService $service,
        private readonly FulfillmentQuery $query,
    ) {}

    public function index(ListFulfillmentRequest $request): LengthAwarePaginator
    {
        $orders = $this->query->list(
            deliveryFlow: $request->input('filter.delivery_flow'),
            department: $request->input('filter.department'),
            onTour: $request->input('filter.on_tour'),
            search: $request->input('filter.search'),
            sort: (string) $request->input('sort', '-packing_ready_at'),
            perPage: (int) $request->input('per_page', 20),
        );

        return OrderData::collect($orders->appends($request->query()));
    }

    public function setFlow(SetDeliveryFlowRequest $request, Order $order): OrderData
    {
        $value = $request->validated('delivery_flow');

        $this->service->setDeliveryFlow(
            $order,
            $value === null ? null : DeliveryFlow::from((string) $value),
        );

        return OrderData::fromModel($this->reloadForResponse($order));
    }

    public function setPackingStatus(SetPackingStatusRequest $request, Order $order): OrderData
    {
        $value = $request->validated('packing_status');

        $this->service->setPackingStatus(
            $order,
            $value === null ? null : PackingStatus::from((string) $value),
        );

        return OrderData::fromModel($this->reloadForResponse($order));
    }

    private function reloadForResponse(Order $order): Order
    {
        return $order->refresh()->load([
            'customer',
            'shippingAddress',
            'shipment',
            'lines.snapshot.lineAttributes',
            'tours',
        ]);
    }
}
