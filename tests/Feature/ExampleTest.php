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
    expect(config('laravel-forduty'))->toHaveKeys(['token', 'base_url']);
});
