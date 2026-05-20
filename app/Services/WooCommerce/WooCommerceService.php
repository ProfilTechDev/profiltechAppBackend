<?php

namespace App\Services\WooCommerce;

use App\Data\WooCommerce\AddressData;
use App\Data\WooCommerce\OrderData;
use App\Data\WooCommerce\OrderLineData;
use App\Data\WooCommerce\ProductData;
use App\Enums\AddressType;
use App\Jobs\WooCommerce\SyncProductJob;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Support\WooCommerce\WooCommerceClient;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Entry point for WooCommerce business operations.
 *
 * Handles syncing both orders and products into the local database, and
 * delegates outbound HTTP to WooCommerceClient.
 */
class WooCommerceService
{
    public function __construct(
        private readonly WooCommerceClient $client,
    ) {}

    /**
     * Persist a WooCommerce order into the local database.
     *
     * Idempotent and race-safe: payloads older than the locally stored
     * version are skipped so out-of-order webhook deliveries cannot
     * overwrite newer state. Lines (and their product snapshots) are
     * replaced on every accepted sync.
     */
    public function syncOrder(OrderData $orderData): Order
    {
        return DB::transaction(function () use ($orderData): Order {
            $existing = Order::where('wc_order_id', $orderData->id)->lockForUpdate()->first();

            if (
                $existing
                && $orderData->date_modified_gmt
                && $existing->wc_modified_at?->gte($orderData->date_modified_gmt)
            ) {
                return $existing;
            }

            $order = Order::updateOrCreate(
                ['wc_order_id' => $orderData->id],
                [
                    'wc_number' => $orderData->number,
                    'status' => $orderData->status,
                    'currency' => $orderData->currency,
                    'total' => $orderData->total,
                    'payment_method_title' => $orderData->payment_method_title,
                    'customer_note' => $orderData->customer_note,
                    'date_created' => $orderData->date_created_gmt,
                    'date_paid' => $orderData->date_paid_gmt,
                    'wc_modified_at' => $orderData->date_modified_gmt,
                ],
            );

            $this->writeCustomerAndAddresses($order, $orderData);

            $order->lines()->delete();

            foreach ($orderData->line_items as $line) {
                $product = $this->upsertProductForOrderLine($line);

                $orderLine = $order->lines()->create([
                    'product_id' => $product->id,
                    'quantity' => $line->quantity,
                ]);

                $this->writeSnapshot($orderLine, $product, $line);
            }

            return $order;
        }, attempts: 5);
    }

    /**
     * Replace the order's customer and billing/shipping addresses with the
     * latest snapshot from the WC payload.
     */
    private function writeCustomerAndAddresses(Order $order, OrderData $orderData): void
    {
        $order->customer()->updateOrCreate(
            ['order_id' => $order->id],
            [
                'first_name' => $orderData->billing->first_name,
                'last_name' => $orderData->billing->last_name,
                'email' => (string) $orderData->billing->email,
                'phone' => $orderData->billing->phone,
            ],
        );

        $this->upsertAddress($order, AddressType::Billing, $orderData->billing);
        $this->upsertAddress($order, AddressType::Shipping, $orderData->shipping);
    }

    private function upsertAddress(Order $order, AddressType $type, AddressData $address): void
    {
        $order->addresses()->updateOrCreate(
            ['type' => $type],
            [
                'first_name' => $address->first_name,
                'last_name' => $address->last_name,
                'company' => $address->company,
                'address_1' => $address->address_1,
                'address_2' => $address->address_2,
                'city' => $address->city,
                'state' => $address->state,
                'postcode' => $address->postcode,
                'country' => $address->country,
                'email' => $address->email,
                'phone' => $address->phone,
            ],
        );
    }

    /**
     * Walk all orders in the given collection, find every unique sellable
     * unit that is not yet in our products table, and batch-fetch them
     * from WooCommerce. After this returns the order sync jobs can run
     * without making any product API calls.
     *
     * @param  Collection<int, OrderData>  $orders
     */
    public function prefetchUnknownProducts(Collection $orders): int
    {
        [$simpleIds, $variationsByParent] = $this->collectUnknownSellableIds($orders);

        $fetched = 0;

        foreach ($this->client->getProducts($simpleIds) as $data) {
            $this->upsertProductFromData($data, parentWcId: null);
            $fetched++;
        }

        foreach ($variationsByParent as $parentWcId => $variationIds) {
            foreach ($this->client->getVariations($parentWcId, $variationIds) as $data) {
                $this->upsertProductFromData($data, parentWcId: $parentWcId);
                $fetched++;
            }
        }

        return $fetched;
    }

    /**
     * @param  Collection<int, OrderData>  $orders
     * @return array{0: array<int, int>, 1: array<int, array<int, int>>}
     *                                                                   Tuple of [simple wc ids, variations grouped by parent wc id].
     */
    private function collectUnknownSellableIds(Collection $orders): array
    {
        $simpleIds = [];
        $variationsByParent = [];
        $seen = [];

        foreach ($orders as $order) {
            foreach ($order->line_items as $line) {
                $wcId = $line->sellableWcId();

                if (isset($seen[$wcId])) {
                    continue;
                }
                $seen[$wcId] = true;

                if (Product::where('wc_id', $wcId)->exists()) {
                    continue;
                }

                $parentWcId = $line->parentWcId();

                if ($parentWcId === null) {
                    $simpleIds[] = $wcId;
                } else {
                    $variationsByParent[$parentWcId][] = $wcId;
                }
            }
        }

        return [$simpleIds, $variationsByParent];
    }

    private function upsertProductFromData(ProductData $data, ?int $parentWcId): Product
    {
        // Variations don't carry categories themselves — they inherit
        // has_thickness from the parent product.
        $hasThickness = $parentWcId !== null
            ? $this->ensureParentProduct($parentWcId)->has_thickness
            : $data->hasThickness();

        return Product::updateOrCreate(
            ['wc_id' => $data->id],
            [
                'wc_parent_id' => $parentWcId,
                'name' => $data->name,
                'is_custom' => $data->is_custom,
                'has_thickness' => $hasThickness,
            ],
        );
    }

    /**
     * Make sure the parent product is in the DB so its has_thickness
     * value is available for variations to inherit. Fetches from WC if
     * we haven't seen it before.
     */
    private function ensureParentProduct(int $parentWcId): Product
    {
        $parent = Product::firstWhere('wc_id', $parentWcId);

        if ($parent) {
            return $parent;
        }

        $parentData = $this->client->getProduct($parentWcId);

        return Product::updateOrCreate(
            ['wc_id' => $parentData->id],
            [
                'wc_parent_id' => null,
                'name' => $parentData->name,
                'is_custom' => $parentData->is_custom,
                'has_thickness' => $parentData->hasThickness(),
            ],
        );
    }

    /**
     * Fetch the canonical state of a single product (or variation) from
     * WooCommerce and upsert it locally. When a variable product is
     * received, fan out a sync job per variation.
     */
    public function syncProduct(int $wcId, ?int $wcParentId = null): Product
    {
        $data = $this->client->getProduct($wcId, $wcParentId);

        $product = $this->upsertProductFromData($data, $wcParentId);

        if ($data->type === 'variable') {
            foreach ($data->variations as $variationId) {
                SyncProductJob::dispatch($variationId, $wcId);
            }
        }

        return $product;
    }

    /**
     * Look up the local Product for an order line, fetching from WC only
     * when we have not seen it before. Existing rows are trusted — they
     * are kept up to date by the product.updated webhook.
     */
    private function upsertProductForOrderLine(OrderLineData $line): Product
    {
        $wcId = $line->sellableWcId();
        $existing = Product::firstWhere('wc_id', $wcId);

        if ($existing) {
            return $existing;
        }

        return $this->syncProduct($wcId, $line->parentWcId());
    }

    /**
     * Persist a transaction-time snapshot of the product as it existed
     * when the order line was created.
     */
    private function writeSnapshot(OrderLine $orderLine, Product $product, OrderLineData $line): void
    {
        $snapshot = $orderLine->snapshot()->updateOrCreate(
            ['order_line_id' => $orderLine->id],
            [
                'name' => $line->name !== '' ? $line->name : $product->name,
                'is_custom' => $product->is_custom,
            ],
        );

        $snapshot->lineAttributes()->delete();

        foreach ($line->extractAttributes() as $position => $attr) {
            $snapshot->lineAttributes()->create([
                'position' => $position,
                'key' => $attr['key'],
                'label' => $attr['label'],
                'value' => $attr['value'],
                'raw_value' => $attr['raw_value'] !== '' ? $attr['raw_value'] : null,
            ]);
        }
    }
}
