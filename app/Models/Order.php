<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents an order synced from WooCommerce.
 */
class Order extends Model
{
    protected $fillable = [
        'wc_order_id',
        'wc_modified_at',
    ];

    protected $casts = [
        'wc_modified_at' => 'datetime',
    ];

    /**
     * @return HasMany<OrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    /**
     * True when any line snapshot is marked as a custom product.
     * Expects `lines.snapshot` to be eager-loaded to avoid N+1 queries.
     */
    protected function isCustom(): Attribute
    {
        return Attribute::make(
            get: fn (): bool => $this->lines->contains(
                fn (OrderLine $line) => $line->snapshot?->is_custom ?? false,
            ),
        );
    }

    /**
     * Limit the query to orders that have at least one line whose
     * product snapshot is marked as custom.
     */
    #[Scope]
    protected function whereIsCustom(Builder $query): void
    {
        $query->whereHas('lines.snapshot', fn ($q) => $q->where('is_custom', true));
    }
}
