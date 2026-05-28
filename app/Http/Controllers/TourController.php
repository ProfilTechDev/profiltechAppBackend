<?php

namespace App\Http\Controllers;

use App\Data\Tours\TourData;
use App\Http\Requests\Tours\AddOrderToTourRequest;
use App\Http\Requests\Tours\ApproveTourRequest;
use App\Http\Requests\Tours\CompleteTourRequest;
use App\Http\Requests\Tours\DestroyTourRequest;
use App\Http\Requests\Tours\ListToursRequest;
use App\Http\Requests\Tours\RemoveOrderFromTourRequest;
use App\Http\Requests\Tours\ReorderTourRequest;
use App\Http\Requests\Tours\ShowTourRequest;
use App\Http\Requests\Tours\StoreTourRequest;
use App\Http\Requests\Tours\UpdateTourRequest;
use App\Models\Order;
use App\Models\Tour;
use App\Queries\TourQuery;
use App\Services\TourService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Delivery-tour management. Thin glue between the planning UI and
 * TourService / TourQuery — all sequencing and state-transition logic
 * lives in the service.
 */
class TourController
{
    public function __construct(
        private readonly TourService $service,
        private readonly TourQuery $query,
    ) {}

    public function index(ListToursRequest $request): LengthAwarePaginator
    {
        $tours = $this->query->list(
            status: $request->input('filter.status'),
            dateRange: $request->input('filter.date_range'),
            sort: (string) $request->input('sort', '-tour_date'),
            perPage: (int) $request->input('per_page', 20),
        );

        return TourData::collect($tours->appends($request->query()));
    }

    public function show(ShowTourRequest $request, Tour $tour): TourData
    {
        $loaded = $this->query->find($tour->id);

        abort_if($loaded === null, Response::HTTP_NOT_FOUND);

        return TourData::fromModel($loaded);
    }

    public function store(StoreTourRequest $request): TourData
    {
        $tour = $this->service->createTour(
            name: (string) $request->validated('name'),
            tourDate: CarbonImmutable::parse((string) $request->validated('tour_date')),
            notes: $request->validated('notes'),
        );

        return TourData::fromModel($tour->loadCount('tourOrders'));
    }

    public function update(UpdateTourRequest $request, Tour $tour): TourData
    {
        $this->service->updateTour($tour, $request->validated());

        return TourData::fromModel($tour->refresh()->loadCount('tourOrders'));
    }

    public function destroy(DestroyTourRequest $request, Tour $tour): JsonResponse
    {
        $this->service->deleteTour($tour);

        return response()->json(['deleted' => true]);
    }

    public function approve(ApproveTourRequest $request, Tour $tour): TourData
    {
        $this->service->approveTour($tour);

        return TourData::fromModel($tour->refresh()->loadCount('tourOrders'));
    }

    public function complete(CompleteTourRequest $request, Tour $tour): TourData
    {
        $this->service->completeTour($tour);

        return TourData::fromModel($tour->refresh()->loadCount('tourOrders'));
    }

    public function addOrder(AddOrderToTourRequest $request, Tour $tour): JsonResponse
    {
        $order = Order::findOrFail($request->validated('order_id'));

        $pivot = $this->service->addOrderToTour(
            $tour,
            $order,
            $request->validated('after_primary'),
            $request->validated('at_primary'),
        );

        return response()->json([
            'tour_id' => $pivot->tour_id,
            'order_id' => $pivot->order_id,
            'primary_sequence' => $pivot->primary_sequence,
            'insert_index' => $pivot->insert_index,
            'display_sequence' => $pivot->display_sequence,
            'added_after_approval' => $pivot->added_after_approval,
        ]);
    }

    public function removeOrder(RemoveOrderFromTourRequest $request, Tour $tour, Order $order): JsonResponse
    {
        $this->service->removeOrderFromTour($tour, $order);

        return response()->json(['removed' => true]);
    }

    public function reorder(ReorderTourRequest $request, Tour $tour): TourData
    {
        $this->service->reorderTour(
            $tour,
            array_map('intval', (array) $request->validated('order_ids')),
        );

        return TourData::fromModel(
            $this->query->find($tour->id) ?? $tour->refresh(),
        );
    }
}
