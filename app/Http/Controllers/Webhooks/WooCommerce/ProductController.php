<?php

namespace App\Http\Controllers\Webhooks\WooCommerce;

use App\Jobs\WooCommerce\SyncProductJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives WooCommerce product.updated webhook callbacks.
 *
 * Dispatches a sync job which fetches the canonical state from WC and
 * upserts the local Product row (plus any variations on variable products).
 */
class ProductController
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id' => ['required', 'integer', 'min:1'],
        ]);

        SyncProductJob::dispatch($validated['id']);

        return response()->json(['accepted' => true], 202);
    }
}
