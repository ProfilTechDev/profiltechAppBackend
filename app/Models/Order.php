<?php

namespace App\Models;

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
}
