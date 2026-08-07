<?php

declare(strict_types=1);

use Concept7\LaravelForduty\Http\Middleware\AddNetworkErrorLoggingHeader;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    Route::middleware('web')->get('/forduty-test', fn (): string => 'ok');

    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', 'https://in.forduty.app');
});

it('attaches the middleware to the web group', function (): void {
    expect(app(Router::class)->getMiddlewareGroups()['web'])
        ->toContain(AddNetworkErrorLoggingHeader::class);
});

it('adds no header by default', function (): void {
    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeaderMissing('NEL');
});

it('adds the policy when enabled', function (): void {
    config()->set('laravel-forduty.nel.enabled', true);

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeader('NEL', '{"report_to":"default","max_age":2592000,"include_subdomains":false,"success_fraction":0,"failure_fraction":1}');
});

it('reflects include_subdomains in the policy', function (): void {
    config()->set('laravel-forduty.nel.enabled', true);
    config()->set('laravel-forduty.nel.include_subdomains', true);

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeader('NEL', '{"report_to":"default","max_age":2592000,"include_subdomains":true,"success_fraction":0,"failure_fraction":1}');
});

it('reflects the sampling fractions in the policy', function (): void {
    config()->set('laravel-forduty.nel.enabled', true);
    config()->set('laravel-forduty.nel.success_fraction', 0.01);
    config()->set('laravel-forduty.nel.failure_fraction', 0.5);

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeader('NEL', '{"report_to":"default","max_age":2592000,"include_subdomains":false,"success_fraction":0.01,"failure_fraction":0.5}');
});

it('accepts a zero max age, which clears a policy the browser holds', function (): void {
    config()->set('laravel-forduty.nel.enabled', true);
    config()->set('laravel-forduty.nel.max_age', 0);

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeader('NEL', '{"report_to":"default","max_age":0,"include_subdomains":false,"success_fraction":0,"failure_fraction":1}');
});

it('accepts numeric strings, as the environment supplies them', function (): void {
    config()->set('laravel-forduty.nel.enabled', true);
    config()->set('laravel-forduty.nel.max_age', '600');
    config()->set('laravel-forduty.nel.failure_fraction', '0.25');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeader('NEL', '{"report_to":"default","max_age":600,"include_subdomains":false,"success_fraction":0,"failure_fraction":0.25}');
});

it('adds no header when the token is missing', function (): void {
    config()->set('laravel-forduty.nel.enabled', true);
    config()->set('laravel-forduty.token', null);

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeaderMissing('NEL');
});

it('adds no header when the base url cannot be used', function (string $baseUrl): void {
    config()->set('laravel-forduty.nel.enabled', true);
    config()->set('laravel-forduty.base_url', $baseUrl);

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeaderMissing('NEL');
})->with([
    '',
    'in.forduty.app',
    'ftp://in.forduty.app',
    'https://in forduty.app',
]);

it('adds no header when the max age is not a whole non-negative number', function (mixed $maxAge): void {
    config()->set('laravel-forduty.nel.enabled', true);
    config()->set('laravel-forduty.nel.max_age', $maxAge);

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeaderMissing('NEL');
})->with([
    'negative' => -1,
    'fractional' => 1.5,
    'non numeric' => 'forever',
    'null' => null,
    'array' => [[]],
]);

it('adds no header when a sampling fraction is out of range', function (string $key, mixed $fraction): void {
    config()->set('laravel-forduty.nel.enabled', true);
    config()->set('laravel-forduty.nel.'.$key, $fraction);

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeaderMissing('NEL');
})->with([
    ['success_fraction', -0.1],
    ['success_fraction', 1.1],
    ['success_fraction', 'half'],
    ['success_fraction', null],
    ['failure_fraction', -0.1],
    ['failure_fraction', 1.1],
    ['failure_fraction', 'half'],
    ['failure_fraction', null],
]);

it('adds no header to routes outside the web group', function (): void {
    config()->set('laravel-forduty.nel.enabled', true);

    Route::middleware('api')->get('/forduty-api', fn (): string => 'ok');

    $this->get('/forduty-api')
        ->assertOk()
        ->assertHeaderMissing('NEL');
});

it('can be excluded without losing the reporting endpoints header', function (): void {
    config()->set('laravel-forduty.nel.enabled', true);

    Route::middleware('web')
        ->get('/forduty-excluded', fn (): string => 'ok')
        ->withoutMiddleware(AddNetworkErrorLoggingHeader::class);

    $this->get('/forduty-excluded')
        ->assertOk()
        ->assertHeaderMissing('NEL')
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/abc123"');
});
