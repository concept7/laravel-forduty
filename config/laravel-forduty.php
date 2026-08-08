<?php

declare(strict_types=1);

return [

    'token' => env('FORDUTY_TOKEN'),

    'base_url' => trim((string) env('FORDUTY_BASE_URL')) ?: 'https://in.forduty.app',

    'nel' => [

        'max_age' => env('FORDUTY_NEL_MAX_AGE', 2592000),

        'include_subdomains' => (bool) env('FORDUTY_NEL_INCLUDE_SUBDOMAINS', false),

        'success_fraction' => env('FORDUTY_NEL_SUCCESS_FRACTION', 0.0),

        'failure_fraction' => env('FORDUTY_NEL_FAILURE_FRACTION', 1.0),

    ],

];
