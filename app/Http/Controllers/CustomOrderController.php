<?php

namespace App\Http\Controllers;

use App\Data\Orders\OrderData;
use App\Models\Order;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Lists orders that contain at least one line whose snapshot is marked
 * as a custom product — i.e. orders the production team needs to act on.
 */
class CustomOrderController
{
    public function list(): LengthAwarePaginator
    {
        $orders = Order::whereIsCustom()
            ->with('lines.snapshot')
            ->latest('wc_modified_at')
            ->paginate(20);

        return OrderData::collect($orders);
    }
}
