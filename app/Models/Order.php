<?php

namespace App\Models;

use App\Enums\AddressType;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Represents an order synced from WooCommerce.
 */
class Order extends Model
{
    protected $fillable = [
        'wc_order_id',
        'wc_number',
        'status',
        'currency',
        'total',
        'payment_method_title',
        'customer_note',
        'date_created',
        'date_paid',
        'wc_modified_at',
    ];

    protected $casts = [
        'total' => 'decimal:2',
        'date_created' => 'immutable_datetime',
        'date_paid' => 'immutable_datetime',
        'wc_modified_at' => 'immutable_datetime',
    ];

    /**
     * @return HasMany<OrderLine, $this>
     */
    public function lines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }

    /**
     * @return HasOne<OrderSubmission, $this>
     */
    public function submission(): HasOne
    {
        return $this->hasOne(OrderSubmission::class);
    }

    /**
     * @return HasOne<OrderCustomer, $this>
     */
    public function customer(): HasOne
    {
        return $this->hasOne(OrderCustomer::class);
    }

    /**
     * @return HasMany<OrderAddress, $this>
     */
    public function addresses(): HasMany
    {
        return $this->hasMany(OrderAddress::class);
    }

    /**
     * @return HasOne<OrderAddress, $this>
     */
    public function billingAddress(): HasOne
    {
        return $this->hasOne(OrderAddress::class)->where('type', AddressType::Billing);
    }

    /**
     * @return HasOne<OrderAddress, $this>
     */
    public function shippingAddress(): HasOne
    {
        return $this->hasOne(OrderAddress::class)->where('type', AddressType::Shipping);
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
