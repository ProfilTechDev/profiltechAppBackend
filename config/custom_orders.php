<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Custom Order — vendor submission settings
    |--------------------------------------------------------------------------
    |
    | Settings for the custom-order send flow: who we CC, who the vendor
    | should reply to, and the available providers we can send orders to.
    | Provider emails are kept in env to avoid leaking vendor contacts
    | into the repository.
    |
    */

    'cc_email' => env('CUSTOM_ORDER_CC_EMAIL'),

    'reply_to' => 'hanne@profiltech.dk',

    'providers' => [
        'byggprofiler' => [
            'name' => 'Byggprofiler',
            'email' => 'mattias@mkieler.com', // 'order@byggprofiler.se',
            'language' => 'da',
        ],
        'romania' => [
            'name' => 'Romænien',
            'email' => 'mattias@mkieler.com', // 'order@romania.com',
            'language' => 'en',
        ],
    ],

    /*
    | Attribute whitelist with optional label and value overrides.
    |
    |   key            The WC meta key (taxonomy slug for pa_*, otherwise raw)
    |   label          Danish label — used by the frontend (always da) and as
    |                  fallback for the email when an _en variant is missing
    |   label_en       English label — used for vendor emails when the provider
    |                  is configured with language=en
    |   value_map      Map from raw display-value → simplified Danish value
    |   value_map_en   Map from raw display-value → simplified English value
    |
    | Keys not listed here are hidden from frontend + email. Add new ones
    | when WC starts sending fields we want to surface.
    */
    'attribute_overrides' => [
        'pa_vaelg-farve' => [
            'label' => 'Farve',
            'label_en' => 'Color',
        ],
        'pa_klikfals-farve' => [
            'label' => 'Farve',
            'label_en' => 'Color',
        ],
        'Indtast længde: (cm)' => [
            'label' => 'Længde (cm)',
            'label_en' => 'Length (cm)',
        ],
        'med-eller-uden-dripstopdug' => [
            'label' => 'Dripstop',
            'label_en' => 'Dripstop',
            'value_map' => [
                'Med' => 'Ja',
                'Uden' => 'Nej',
            ],
            'value_map_en' => [
                'Med' => 'Yes',
                'Uden' => 'No',
            ],
        ],
        'med-eller-uden-dripstop-antikondens-dug' => [
            'label' => 'Dripstop',
            'label_en' => 'Dripstop',
            'value_map' => [
                'Med' => 'Ja',
                'Uden' => 'Nej',
            ],
            'value_map_en' => [
                'Med' => 'Yes',
                'Uden' => 'No',
            ],
        ],
    ],

    /*
    | Map of color slugs (as stored in WC's attribute taxonomies) to hex
    | values. Used to enrich attribute output with a `color` field so the
    | frontend can render a visual swatch next to the color label.
    */
    'colors' => [
        'antracit-gra-ral-7016' => '#293133',
        'blaa-ral-5001' => '#1E2460',
        'brun-ral-8028' => '#4E3B31',
        'graa-ral-7012' => '#4F5358',
        'groen-ral-6003' => '#4E5B31',
        'gul-ral-1002' => '#D2B04C',
        'hvid-ral-9010' => '#F1ECE1',
        'lys-gul-ral-1015' => '#E5D8B9',
        'lysegra-7038' => '#B5B8B1',
        'mikado-bauxit-sort' => '#1A1A1A',
        'mikado-terrakotta-ral-8023' => '#A05C2C',
        'moerk-roed-ral-3009' => '#65170d',
        'moerk-silver-ral-9007' => '#8F8F8C',
        'silkegraa-ral-7044' => '#CBC9C0',
        'silver-ral-9006' => '#A1A1A0',
        'sort-9005' => '#0A0A0A',
        'svensk-rod-ral-3013' => '#b10f0f',
        'teglroed-ral-8004' => '#a63528',
    ],

];
