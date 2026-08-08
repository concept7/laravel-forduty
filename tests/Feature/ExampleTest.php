<?php

declare(strict_types=1);

use Concept7\LaravelForduty\LaravelForduty;

it('resolves the singleton', function (): void {
    expect(app(LaravelForduty::class))->toBeInstanceOf(LaravelForduty::class);
});

it('returns the same instance from the container', function (): void {
    expect(app(LaravelForduty::class))->toBe(app(LaravelForduty::class));
});

it('merges the package config', function (): void {
    expect(config('laravel-forduty'))->toHaveKeys(['token', 'base_url', 'nel'])
        ->and(config('laravel-forduty.base_url'))->toBe('https://in.forduty.app');
});

it('defaults the network error logging policy', function (): void {
    expect(config('laravel-forduty.nel'))
        ->toMatchArray([
            'max_age' => 2592000,
            'include_subdomains' => false,
            'success_fraction' => 0.0,
            'failure_fraction' => 1.0,
        ]);
});

it('falls back to the default base url when the environment value is blank', function (string $value): void {
    $_SERVER['FORDUTY_BASE_URL'] = $value;

    try {
        $config = require dirname(__DIR__, 2).'/config/laravel-forduty.php';
    } finally {
        unset($_SERVER['FORDUTY_BASE_URL']);
    }

    expect($config['base_url'])->toBe('https://in.forduty.app');
})->with(['', ' ']);

it('keeps a zero network error logging value instead of treating it as unset', function (string $variable, string $key): void {
    $_SERVER[$variable] = '0';

    try {
        $config = require dirname(__DIR__, 2).'/config/laravel-forduty.php';
    } finally {
        unset($_SERVER[$variable]);
    }

    expect($config['nel'][$key])->toEqual(0);
})->with([
    ['FORDUTY_NEL_MAX_AGE', 'max_age'],
    ['FORDUTY_NEL_SUCCESS_FRACTION', 'success_fraction'],
    ['FORDUTY_NEL_FAILURE_FRACTION', 'failure_fraction'],
]);
