<?php

namespace App\Http\Controllers\Api\Webhooks;

use App\Http\Controllers\Controller;
use App\Jobs\SyncWooCommerceProductJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives WooCommerce product.updated webhook callbacks.
 *
 * Dispatches a sync job which fetches the canonical state from WC and
 * upserts the local Product row (plus any variations on variable products).
 */
class WooCommerceProductController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ]);

        SyncWooCommerceProductJob::dispatch($validated['id']);

        return response()->json(['accepted' => true], 202);
    }
}
