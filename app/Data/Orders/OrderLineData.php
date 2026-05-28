<?php

namespace App\Data\Orders;

use App\Enums\ProductDepartment;
use App\Models\OrderLine;
use Spatie\LaravelData\Attributes\DataCollectionOf;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\DataCollection;

/**
 * One line item of an order with its product snapshot.
 *
 * `name`, `is_custom` and `attributes` are read from the OrderLine's
 * snapshot relation — the transaction-time copy of the product, not
 * its current state. Attributes are filtered via the snapshot's
 * `visibleAttributes()` method so internal keys never leak out.
 */
class OrderLineData extends Data
{
    /**
     * @param  DataCollection<int, AttributeData>  $attributes
     */
    public function __construct(
        public int $id,
        public string $name,
        public int $quantity,
        public bool $is_custom,
        public bool $has_thickness,
        public ?ProductDepartment $department,
        #[DataCollectionOf(AttributeData::class)]
        public DataCollection $attributes,
    ) {}

    public static function fromModel(OrderLine $line): self
    {
        $visible = $line->snapshot?->visibleAttributes() ?? collect();

        return new self(
            id: $line->id,
            name: $line->snapshot?->name ?? '',
            quantity: $line->quantity,
            is_custom: $line->snapshot?->is_custom ?? false,
            has_thickness: $line->product?->has_thickness ?? false,
            department: $line->snapshot?->department,
            attributes: AttributeData::collect($visible, DataCollection::class),
        );
    }
}
