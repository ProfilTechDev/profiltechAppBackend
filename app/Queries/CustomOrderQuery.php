<?php

namespace App\Queries;

use App\Data\CustomOrders\ProviderData;
use App\Http\Filters\Orders\OrderSearchFilter;
use App\Http\Filters\Orders\OrderStatusFilter;
use App\Http\Filters\Orders\SubmissionStatusFilter;
use App\Models\Order;
use App\Models\OrderSubmission;
use App\Queries\Concerns\AppliesSort;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Read-side counterpart to `CustomOrderService` — owns every query
 * the custom-orders domain exposes to controllers / CLI / future
 * consumers. Methods take typed parameters so callers don't pass
 * a `Request` instance into the read layer.
 */
class CustomOrderQuery
{
    use AppliesSort;

    private const ALLOWED_SORT_FIELDS = ['wc_modified_at', 'date_created', 'total'];

    /**
     * Paginated list of orders that contain at least one custom line.
     * Filter/sort params are optional; unknown sort fields fall back
     * to the default silently.
     */
    public function list(
        ?string $orderStatus = null,
        ?string $submissionStatus = null,
        ?string $search = null,
        string $sort = '-wc_modified_at',
        int $perPage = 20,
    ): LengthAwarePaginator {
        $query = Order::query()->whereIsCustom();

        (new OrderStatusFilter)($query, $orderStatus);
        (new SubmissionStatusFilter)($query, $submissionStatus);
        (new OrderSearchFilter)($query, $search);

        $this->applySort($query, $sort, self::ALLOWED_SORT_FIELDS, '-wc_modified_at');

        return $query
            ->with(['lines.snapshot.lineAttributes', 'lines.product', 'submission', 'customer'])
            ->paginate($perPage);
    }

    /**
     * The order's submission with lines eager-loaded, or null if no
     * submission has been started yet.
     */
    public function getSubmission(Order $order): ?OrderSubmission
    {
        return $order->submission()->with('lines')->first();
    }

    /**
     * Available vendors that submissions can be sent to. Backed by
     * `config/custom_orders.php` rather than the DB.
     *
     * @return Collection<int, ProviderData>
     */
    public function providers(): Collection
    {
        return collect(config('custom_orders.providers'))
            ->map(fn (array $provider, string $id): ProviderData => new ProviderData(
                id: $id,
                name: $provider['name'],
                email: $provider['email'],
                language: $provider['language'],
            ))
            ->values();
    }
}
