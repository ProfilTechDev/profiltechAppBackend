<?php

namespace App\Http\Controllers;

use App\Data\CustomOrders\SubmissionData;
use App\Data\Orders\OrderData;
use App\Http\Requests\CustomOrderUpdateRequest;
use App\Models\Order;
use App\Services\CustomOrderService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;

/**
 * Lists orders that contain at least one line whose snapshot is marked
 * as a custom product, and orchestrates the multi-step "send to vendor"
 * workflow on top of them.
 */
class CustomOrderController
{
    public function __construct(
        private readonly CustomOrderService $service,
    ) {}

    public function list(): LengthAwarePaginator
    {
        $orders = Order::whereIsCustom()
            ->with(['lines.snapshot', 'submission', 'customer'])
            ->latest('wc_modified_at')
            ->paginate(20);

        return OrderData::collect($orders);
    }

    public function submission(Order $order): JsonResponse
    {
        $submission = $this->service->getSubmission($order);

        return response()->json(
            $submission ? SubmissionData::from($submission) : null,
        );
    }

    public function update(Order $order, CustomOrderUpdateRequest $request): JsonResponse
    {
        $this->service->updateSubmission(
            $order,
            lines: $request->lines(),
            providerFields: $request->providerFields(),
        );

        return response()->json(['saved' => true]);
    }

    public function send(Order $order): JsonResponse
    {
        $this->service->sendSubmission($order);

        return response()->json(['queued' => true]);
    }
}
