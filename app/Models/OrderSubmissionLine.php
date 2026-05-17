<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A single line on an OrderSubmission. `quantity` is what we plan to
 * order from the vendor — it can differ from the original OrderLine
 * quantity (which reflects what the customer actually bought).
 */
class OrderSubmissionLine extends Model
{
    protected $fillable = [
        'order_submission_id',
        'order_line_id',
        'quantity',
        'thickness',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'thickness' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<OrderSubmission, $this>
     */
    public function submission(): BelongsTo
    {
        return $this->belongsTo(OrderSubmission::class, 'order_submission_id');
    }

    /**
     * @return BelongsTo<OrderLine, $this>
     */
    public function orderLine(): BelongsTo
    {
        return $this->belongsTo(OrderLine::class);
    }
}
