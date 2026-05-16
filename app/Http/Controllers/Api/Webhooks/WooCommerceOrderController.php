<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Data\WooCommerce\OrderData;
use App\Http\Controllers\Controller;
use App\Jobs\SyncWooCommerceOrderJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives WooCommerce order.updated webhook callbacks.
 *
 * Hydrates the payload into an OrderData DTO, then dispatches the
 * heavy lifting to a queue job so the webhook returns quickly.
 */
class WooCommerceOrderController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $order = OrderData::from($request->all());

        SyncWooCommerceOrderJob::dispatch($order);

        return response()->json(['accepted' => true], 202);
    }
}
