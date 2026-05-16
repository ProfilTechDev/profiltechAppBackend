<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A snapshot of a Product as it existed at the time an OrderLine was created.
 *
 * Fields here are intentionally denormalised from the products table and
 * are never overwritten by later product updates — they represent what
 * the customer actually purchased.
 */
class OrderLineProduct extends Model
{
    protected $fillable = [
        'order_line_id',
        'name',
        'is_custom',
    ];

    protected $casts = [
        'is_custom' => 'boolean',
    ];

    /**
     * @return BelongsTo<OrderLine, $this>
     */
    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(OrderLine::class);
    }
}
