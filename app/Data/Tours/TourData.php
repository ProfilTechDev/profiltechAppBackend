<?php

namespace App\Data\Tours;

use App\Enums\TourStatus;
use App\Models\Tour;
use Carbon\CarbonImmutable;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * Full tour representation. List endpoints leave `orders` as null
 * (and surface `orders_count` instead); detail endpoints populate
 * it with the ordered slots — the same shape so the frontend can
 * use one type.
 */
class TourData extends Data
{
    /**
     * @param  DataCollection<int, TourOrderData>|null  $orders
     */
    public function __construct(
        public int $id,
        public string $name,
        public CarbonImmutable $tour_date,
        public TourStatus $status,
        public ?string $notes,
        public ?CarbonImmutable $approved_at,
        public ?CarbonImmutable $completed_at,
        public ?CarbonImmutable $created_at,
        public ?CarbonImmutable $updated_at,
        public int $orders_count,
        #[DataCollectionOf(TourOrderData::class)]
        public ?DataCollection $orders,
    ) {}

    public static function fromModel(Tour $tour): self
    {
        $orders = $tour->relationLoaded('tourOrders')
            ? TourOrderData::collect($tour->tourOrders, DataCollection::class)
            : null;

        return new self(
            id: $tour->id,
            name: $tour->name,
            tour_date: $tour->tour_date,
            status: $tour->status,
            notes: $tour->notes,
            approved_at: $tour->approved_at,
            completed_at: $tour->completed_at,
            created_at: $tour->created_at?->toImmutable(),
            updated_at: $tour->updated_at?->toImmutable(),
            orders_count: (int) ($tour->tour_orders_count ?? $tour->tourOrders?->count() ?? 0),
            orders: $orders,
        );
    }
}
