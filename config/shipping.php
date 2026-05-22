<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Shipping categorisation rules
    |--------------------------------------------------------------------------
    |
    | The Profiltech warehouse module routes orders through different
    | fulfilment flows depending on how the order is shipped. To keep the
    | classification stable in face of WC label changes (admin-edited
    | thresholds in titles like "køb over 9800 for fri levering"), we
    | match on stable IDs from the WC Table Rate plugin instead of
    | parsing strings.
    |
    | Order of evaluation in ShippingCategorizerService:
    |   1. `rate_ids`        — most specific: a Table Rate row id captured
    |                          on the order via the WP-side mu-plugin's
    |                          `_pft_rate_id` meta.
    |   2. `instance_ids`    — the WC zone-method id. Used when an
    |                          instance is entirely dedicated to one
    |                          category (e.g. 14 is the pickup bucket).
    |   3. `method_ids`      — the WC method type slug. Catches non-Table-
    |                          Rate methods (Shipmondo variants, free
    |                          shipping) that have no rate_id at all.
    |
    | The first match wins. Anything left over is `unknown` — the
    | warehouse UI surfaces those so an operator can categorise + add to
    | this config.
    |
    */

    'rate_ids' => [
        'pickup' => [
            106,  // instance 25 "Afhentning"
        ],
        'self_delivery' => [
            // instance 11 — kranbil + fri levering
            25, 35, 46, 73, 84, 110, 113, 114,
            // instance 23 — Bornholm-leverancer
            98, 99, 100, 101, 112,
        ],
        'external_carrier' => [
            // instance 11 — Danske Fragtmænd
            59, 61,
            // instance 23 — Danske Fragtmænd til Bornholm
            102, 109,
        ],
    ],

    'instance_ids' => [
        // Hele instances dedikeret til én kategori
        'pickup' => [14],
        'external_carrier' => [
            20, 21, 22,            // Shipmondo-zones
            35, 36, 37, 38,        // Danske Fragtmænd-dedikerede zones
        ],
    ],

    'method_ids' => [
        'external_carrier' => [
            'shipmondo',
            'shipmondo_shipping_gls',
            'shipmondo_shipping_gls_private',
            'shipmondo_shipping_gls_business',
            'shipmondo_shipping_custom',
        ],
        'self_delivery' => [
            'free_shipping',
        ],
    ],

];
