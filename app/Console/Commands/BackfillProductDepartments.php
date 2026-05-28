<?php

namespace App\Console\Commands;

use App\Enums\ProductDepartment;
use App\Models\Product;
use App\Services\ProductService;
use App\Support\WooCommerce\WooCommerceClient;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * One-off (idempotent) backfill of Product.department after the column
 * was added. Only touches rows where department is still NULL.
 *
 *   1. For top-level products, re-fetch from WC to read categories and
 *      run them through ProductDepartment::fromCategories(). The
 *      per-row write goes through ProductService::backfillDepartment.
 *   2. Variations inherit the resolved value from their parent in a
 *      single UPDATE…JOIN, matching the sync-time behaviour.
 *   3. Order line snapshots inherit from the (current) related Product
 *      — the column didn't exist at order time, so we treat the live
 *      product's department as the best available approximation for
 *      historical orders.
 *
 * Steps 2 and 3 deliberately use raw bulk SQL rather than per-row
 * mutations through a service: the alternative is N round-trips for
 * potentially >10k variations and >100k snapshot rows. The convention
 * "services own mutations" is suspended for bulk data migrations,
 * the same way Laravel migrations themselves don't use Eloquent.
 */
#[Signature('backfill:product-departments {--chunk=50}')]
#[Description('Populate Product.department + OrderLineProduct.department for existing rows.')]
class BackfillProductDepartments extends Command
{
    public function handle(WooCommerceClient $client, ProductService $products): int
    {
        $chunkSize = (int) $this->option('chunk');

        $missingParents = Product::query()
            ->whereNull('department')
            ->whereNull('wc_parent_id')
            ->pluck('wc_id');

        $this->info("Re-fetching {$missingParents->count()} parent product(s) from WooCommerce...");

        foreach ($missingParents->chunk($chunkSize) as $chunk) {
            $productData = $client->getProducts($chunk->all());

            foreach ($productData as $data) {
                $product = Product::where('wc_id', $data->id)->first();

                if ($product === null) {
                    continue;
                }

                $products->backfillDepartment(
                    $product,
                    ProductDepartment::fromCategories($data->categories),
                );
            }
        }

        $variationsUpdated = DB::update(
            'UPDATE products v
             INNER JOIN products p ON p.wc_id = v.wc_parent_id
             SET v.department = p.department
             WHERE v.department IS NULL
               AND v.wc_parent_id IS NOT NULL
               AND p.department IS NOT NULL'
        );

        $this->info("Propagated department to {$variationsUpdated} variation(s).");

        $snapshotsUpdated = DB::update(
            'UPDATE order_line_products olp
             INNER JOIN order_lines ol ON ol.id = olp.order_line_id
             INNER JOIN products p ON p.id = ol.product_id
             SET olp.department = p.department
             WHERE olp.department IS NULL
               AND p.department IS NOT NULL'
        );

        $this->info("Backfilled {$snapshotsUpdated} order line snapshot(s).");

        return self::SUCCESS;
    }
}
