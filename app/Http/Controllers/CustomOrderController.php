<?php

namespace App\Http\Controllers;

use App\Data\CustomOrders\SubmissionData;
use App\Data\Orders\OrderData;
use App\Http\Requests\CustomOrders\CustomOrderUpdateRequest;
use App\Http\Requests\CustomOrders\ListCustomOrdersRequest;
use App\Models\Order;
use App\Queries\CustomOrderQuery;
use App\Services\CustomOrderService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;

/**
 * Lists orders that contain at least one line whose snapshot is marked
 * as a custom product, and orchestrates the multi-step "send to vendor"
 * workflow on top of them.
 *
 * Reads are delegated to `CustomOrderQuery`; commands go through
 * `CustomOrderService`. Controller is purely a translator between the
 * HTTP request and the typed domain layers.
 */
class CustomOrderController
{
    public function __construct(
        private readonly CustomOrderService $service,
        private readonly CustomOrderQuery $query,
    ) {}

    public function list(ListCustomOrdersRequest $request): LengthAwarePaginator
    {
        $orders = $this->query->list(
            orderStatus: $request->input('filter.order_status'),
            submissionStatus: $request->input('filter.submission_status'),
            search: $request->input('filter.search'),
            sort: (string) $request->input('sort', '-wc_modified_at'),
            perPage: (int) $request->input('per_page', 20),
        );

        return OrderData::collect($orders->appends($request->query()));
    }

    public function providers(): Collection
    {
        return $this->query->providers();
    }

    public function submission(Order $order): JsonResponse
    {
        $submission = $this->query->getSubmission($order);

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

    /**
     * Mark the order's sent submission as received from the vendor.
     * The downstream listener stamps `packing_ready_at` so the order
     * appears in the planning list.
     */
    public function received(Order $order): JsonResponse
    {
        $submission = $order->submission()->firstOrFail();

        $this->service->markSubmissionReceived($submission);

        return response()->json(['received' => true]);
    }
}
