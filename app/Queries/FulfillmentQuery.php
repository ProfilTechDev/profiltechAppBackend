<?php

namespace App\Queries;

use App\Http\Filters\Fulfillment\DeliveryFlowFilter;
use App\Http\Filters\Fulfillment\DepartmentFilter;
use App\Http\Filters\Fulfillment\OnTourFilter;
use App\Http\Filters\Orders\OrderSearchFilter;
use App\Models\Order;
use App\Queries\Concerns\AppliesSort;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Read-side counterpart to FulfillmentService. The planning UI talks
 * to this — it surfaces orders that are ready to pack (i.e.
 * `packing_ready_at` is set, and the WC status isn't a closed state).
 *
 * Custom orders not yet returned by the vendor are filtered out
 * implicitly: their `packing_ready_at` stays null until admin marks
 * the submission as received.
 */
class FulfillmentQuery
{
    use AppliesSort;

    private const ALLOWED_SORT_FIELDS = [
        'date_created',
        'date_paid',
        'packing_ready_at',
        'wc_modified_at',
        'total',
    ];

    /**
     * Orders eligible for the planning list. Excludes WC closed states
     * (`completed`, `cancelled`, `refunded`) — those have already left
     * the warehouse or were never going to. Filters and sort are
     * optional; unknown sort values fall back to the default silently.
     */
    public function list(
        ?string $deliveryFlow = null,
        ?string $department = null,
        ?string $onTour = null,
        ?string $search = null,
        string $sort = '-packing_ready_at',
        int $perPage = 20,
    ): LengthAwarePaginator {
        $query = Order::query()
            ->whereNotNull('packing_ready_at')
            ->whereNotIn('status', ['completed', 'cancelled', 'refunded']);

        (new DeliveryFlowFilter)($query, $deliveryFlow);
        (new DepartmentFilter)($query, $department);
        (new OnTourFilter)($query, $onTour);
        (new OrderSearchFilter)($query, $search);

        $this->applySort($query, $sort, self::ALLOWED_SORT_FIELDS, '-packing_ready_at');

        return $query
            ->with([
                'customer',
                'shippingAddress',
                'shipment',
                'lines.snapshot.lineAttributes',
                'tours' => fn ($q) => $q->select('tours.id', 'tours.name', 'tours.tour_date', 'tours.status'),
            ])
            ->paginate($perPage);
    }
}
