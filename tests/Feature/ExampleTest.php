<?php

declare(strict_types=1);

use Concept7\LaravelForduty\LaravelForduty;

it('resolves the singleton', function () {
    expect(app(LaravelForduty::class))->toBeInstanceOf(LaravelForduty::class);
});

it('returns the same instance from the container', function () {
    expect(app(LaravelForduty::class))->toBe(app(LaravelForduty::class));
});

it('merges the package config', function () {
    expect(config('laravel-forduty'))->toHaveKeys(['token', 'base_url'])
        ->and(config('laravel-forduty.base_url'))->toBe('https://in.forduty.app');
});

it('falls back to the default base url when the environment value is blank', function (string $value) {
    $_SERVER['FORDUTY_BASE_URL'] = $value;

    try {
        $config = require dirname(__DIR__, 2).'/config/laravel-forduty.php';
    } finally {
        unset($_SERVER['FORDUTY_BASE_URL']);
    }

    expect($config['base_url'])->toBe('https://in.forduty.app');
})->with(['', ' ']);
