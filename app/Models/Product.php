<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A sellable unit synced from WooCommerce — either a simple product or
 * a variation. Variations carry their parent product's wc_id in wc_parent_id.
 */
class Product extends Model
{
    protected $fillable = [
        'wc_id',
        'wc_parent_id',
        'name',
        'is_custom',
        'has_thickness',
    ];

    protected $casts = [
        'is_custom' => 'boolean',
        'has_thickness' => 'boolean',
    ];

    /**
     * @return HasMany<OrderLine, $this>
     */
    public function orderLines(): HasMany
    {
        return $this->hasMany(OrderLine::class);
    }
}
