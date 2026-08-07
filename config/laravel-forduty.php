<?php

declare(strict_types=1);

/**
 * A present-but-blank environment value must not beat the default, and zero is
 * a meaningful setting for both the policy lifetime and the sampling rates, so
 * emptiness decides the fallback rather than truthiness.
 */
$fallback = fn (mixed $value, mixed $default): mixed => blank($value) ? $default : $value;

return [

    'token' => env('FORDUTY_TOKEN'),

    'base_url' => trim((string) env('FORDUTY_BASE_URL')) ?: 'https://in.forduty.app',

    'nel' => [

        'enabled' => (bool) env('FORDUTY_NEL_ENABLED', false),

        'max_age' => $fallback(env('FORDUTY_NEL_MAX_AGE'), 2592000),

        'include_subdomains' => (bool) env('FORDUTY_NEL_INCLUDE_SUBDOMAINS', false),

        'success_fraction' => $fallback(env('FORDUTY_NEL_SUCCESS_FRACTION'), 0.0),

        'failure_fraction' => $fallback(env('FORDUTY_NEL_FAILURE_FRACTION'), 1.0),

    ],

];
