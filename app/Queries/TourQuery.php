<?php

namespace App\Queries;

use App\Http\Filters\Tours\TourDateRangeFilter;
use App\Http\Filters\Tours\TourStatusFilter;
use App\Models\Tour;
use App\Queries\Concerns\AppliesSort;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Read-side counterpart to TourService. Owns all list/show queries for
 * the tour-planning UI.
 */
class TourQuery
{
    use AppliesSort;

    private const ALLOWED_SORT_FIELDS = ['tour_date', 'created_at', 'name'];

    /**
     * Paginated list of tours. Default sort surfaces the next upcoming
     * tours first (oldest future date at the top, then today, then
     * past). Filters are evaluated against tour_status and tour_date.
     */
    public function list(
        ?string $status = null,
        ?string $dateRange = null,
        string $sort = '-tour_date',
        int $perPage = 20,
    ): LengthAwarePaginator {
        $query = Tour::query()->withCount('tourOrders');

        (new TourStatusFilter)($query, $status);
        (new TourDateRangeFilter)($query, $dateRange);

        $this->applySort($query, $sort, self::ALLOWED_SORT_FIELDS, '-tour_date');

        return $query->paginate($perPage);
    }

    /**
     * Single tour with its ordered orders + the order context the
     * planning UI needs to render the sequence list. Lines and
     * snapshots are eager-loaded so the per-department breakdown is
     * available without N+1.
     */
    public function find(int $tourId): ?Tour
    {
        return Tour::query()
            ->with([
                'tourOrders.order.customer',
                'tourOrders.order.shippingAddress',
                'tourOrders.order.lines.snapshot',
            ])
            ->find($tourId);
    }
}
