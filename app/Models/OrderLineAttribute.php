<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One key/value attribute attached to an OrderLineProduct snapshot.
 * Represents a single piece of WC line-item meta_data — typically
 * configuration like colour, dimension or option chosen by the customer.
 */
class OrderLineAttribute extends Model
{
    protected $fillable = [
        'order_line_product_id',
        'position',
        'key',
        'label',
        'value',
        'raw_value',
    ];

    protected $casts = [
        'position' => 'integer',
    ];

    /**
     * @return BelongsTo<OrderLineProduct, $this>
     */
    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(OrderLineProduct::class, 'order_line_product_id');
    }
}
