<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Department mapping rules
    |--------------------------------------------------------------------------
    |
    | Maps WooCommerce category slugs to internal warehouse departments.
    | Evaluated in priority order by ProductDepartment::fromCategories():
    |
    |   1. steel_plates — any category slug below wins. Typically the
    |      parent slugs `staaltag` and `kliktag`, which WC also assigns
    |      to products in their child categories. Add child slugs here
    |      if a WC setup doesn't auto-tag the parent.
    |   2. flashings   — slugs that should map to the flashings dept.
    |                    `inddaekninger` is the canonical one.
    |   3. accessories — implicit catch-all. Anything that matches
    |                    neither steel nor flashings lands here.
    |
    */

    'rules' => [
        'steel_plates' => [
            'staaltag',
            'kliktag',
        ],

        'flashings' => [
            'inddaekninger',
        ],
    ],

];
