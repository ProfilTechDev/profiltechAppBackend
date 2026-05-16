<?php

namespace App\Http\Controllers\Webhooks\WooCommerce;

use App\Data\WooCommerce\OrderData;
use App\Jobs\WooCommerce\SyncOrderJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives WooCommerce order.updated webhook callbacks.
 *
 * Hydrates the payload into an OrderData DTO, then dispatches the
 * heavy lifting to a queue job so the webhook returns quickly.
 */
class OrderController
{
    public function __invoke(Request $request): JsonResponse
    {
        $order = OrderData::from($request->all());

        SyncOrderJob::dispatch($order);

        return response()->json(['accepted' => true], 202);
    }
}
