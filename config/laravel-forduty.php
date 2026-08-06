<?php

declare(strict_types=1);

return [

    'token' => env('FORDUTY_TOKEN'),

    'base_url' => trim((string) env('FORDUTY_BASE_URL')) ?: 'https://in.forduty.app',

];
