<?php

namespace App\Models;

use App\Enums\ShippingCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Snapshot of an order's chosen shipping method. One row per order
 * (we collapse the rare multi-shipping_line case to the highest-cost
 * line at sync time — see WooCommerceService).
 *
 * `category` is derived by ShippingCategorizerService and persisted
 * here so warehouse-frontend can filter without re-running the
 * categoriser on every query.
 */
class OrderShipment extends Model
{
    protected $fillable = [
        'order_id',
        'category',
        'rate_id',
        'method_id',
        'instance_id',
        'method_title',
        'total',
    ];

    protected $casts = [
        'category' => ShippingCategory::class,
        'rate_id' => 'integer',
        'instance_id' => 'integer',
        'total' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
