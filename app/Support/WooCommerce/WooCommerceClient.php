<?php

namespace App\Support\WooCommerce;

use App\Data\WooCommerce\OrderData;
use App\Data\WooCommerce\ProductData;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Thin HTTP wrapper around the WooCommerce REST API v3.
 *
 * Authenticates outbound requests with the store's REST consumer key/secret
 * over HTTPS basic auth.
 */
class WooCommerceClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $consumerKey,
        private readonly string $consumerSecret,
    ) {}

    /**
     * Fetch every order modified after the given ISO-8601 timestamp.
     *
     * Pages through results using the X-WP-TotalPages response header.
     *
     * @return Collection<int, OrderData>
     */
    public function getOrdersModifiedAfter(string $modifiedAfter, int $perPage = 100): Collection
    {
        $first = $this->fetchOrdersPage($modifiedAfter, $perPage, page: 1);
        $payloads = collect($first->json());
        $totalPages = (int) $first->header('X-WP-TotalPages');

        for ($page = 2; $page <= $totalPages; $page++) {
            $payloads = $payloads->concat($this->fetchOrdersPage($modifiedAfter, $perPage, $page)->json());
        }

        return $payloads->map(fn (array $payload): OrderData => OrderData::from($payload));
    }

    /**
     * Fetch a single sellable unit (simple product or variation).
     *
     * Pass $parentWcId when fetching a variation — WC exposes variations on
     * /products/{parent}/variations/{id}, not directly on /products/{id}.
     *
     * Responses are cached briefly so concurrent or back-to-back lookups
     * for the same id do not hammer WC. A short TTL keeps us responsive
     * to product changes (the product.updated webhook is the canonical
     * way to invalidate state — this cache is only a burst dampener).
     */
    public function getProduct(int $wcId, ?int $parentWcId = null): ProductData
    {
        return Cache::remember(
            "wc-product:{$wcId}",
            now()->addMinutes(5),
            fn (): ProductData => $this->fetchProduct($wcId, $parentWcId),
        );
    }

    /**
     * Fetch many simple/parent products in one round trip per chunk of 100.
     *
     * @param  array<int, int>  $wcIds
     * @return Collection<int, ProductData>
     */
    public function getProducts(array $wcIds): Collection
    {
        return $this->batchFetch('products', $wcIds);
    }

    /**
     * Fetch many variations of a single parent in one round trip per chunk of 100.
     *
     * @param  array<int, int>  $wcIds
     * @return Collection<int, ProductData>
     */
    public function getVariations(int $parentWcId, array $wcIds): Collection
    {
        return $this->batchFetch("products/{$parentWcId}/variations", $wcIds);
    }

    /**
     * Fire one HTTP request per chunk of 100 ids in parallel via Http::pool.
     *
     * @param  array<int, int>  $wcIds
     * @return Collection<int, ProductData>
     */
    private function batchFetch(string $path, array $wcIds): Collection
    {
        if ($wcIds === []) {
            return collect();
        }

        $chunks = array_chunk(array_values(array_unique($wcIds)), 100);

        $responses = Http::pool(fn (Pool $pool) => array_map(
            fn (array $chunk) => $this->configurePoolRequest($pool)->get($path, [
                'include' => implode(',', $chunk),
                'per_page' => 100,
            ]),
            $chunks,
        ));

        $results = collect();

        foreach ($responses as $response) {
            if ($response->failed()) {
                $url = (string) $response->effectiveUri();

                throw new RuntimeException(
                    "WooCommerce API request failed: HTTP {$response->status()} {$url} - {$response->body()}"
                );
            }

            foreach ($response->json() as $payload) {
                $results->push(ProductData::from($payload));
            }
        }

        return $results;
    }

    private function configurePoolRequest(Pool $pool): PendingRequest
    {
        return $pool
            ->baseUrl(rtrim($this->baseUrl, '/').'/wp-json/wc/v3/')
            ->withBasicAuth($this->consumerKey, $this->consumerSecret)
            ->acceptJson()
            ->timeout(30);
    }

    private function fetchProduct(int $wcId, ?int $parentWcId): ProductData
    {
        $path = $parentWcId !== null
            ? "products/{$parentWcId}/variations/{$wcId}"
            : "products/{$wcId}";

        $response = $this->request()->get($path);

        if ($response->failed()) {
            $url = (string) $response->effectiveUri();

            throw new RuntimeException(
                "WooCommerce API request failed: HTTP {$response->status()} {$url} - {$response->body()}"
            );
        }

        return ProductData::from($response->json());
    }

    private function fetchOrdersPage(string $modifiedAfter, int $perPage, int $page): Response
    {
        $response = $this->request()->get('orders', [
            'modified_after' => $modifiedAfter,
            'orderby' => 'modified',
            'order' => 'asc',
            'per_page' => $perPage,
            'page' => $page,
        ]);

        if ($response->failed()) {
            $url = (string) $response->effectiveUri();

            throw new RuntimeException(
                "WooCommerce API request failed: HTTP {$response->status()} {$url} - {$response->body()}"
            );
        }

        return $response;
    }

    private function request(): PendingRequest
    {
        return Http::baseUrl(rtrim($this->baseUrl, '/').'/wp-json/wc/v3/')
            ->withBasicAuth($this->consumerKey, $this->consumerSecret)
            ->acceptJson()
            ->timeout(30);
    }
}
