<?php

declare(strict_types=1);

use Concept7\LaravelForduty\Http\Middleware\AddNetworkErrorLoggingHeader;
use Concept7\LaravelForduty\Http\Middleware\AddReportingEndpointsHeader;
use Concept7\LaravelForduty\LaravelFordutyServiceProvider;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Http\Kernel as HttpKernelContract;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', 'https://in.forduty.app');

    Route::middleware('web')->get('/forduty-test', fn (): string => 'ok');
});

it('registers no middleware on the global stack', function (): void {
    expect(app(HttpKernelContract::class)->getGlobalMiddleware())
        ->not->toContain(AddReportingEndpointsHeader::class)
        ->not->toContain(AddNetworkErrorLoggingHeader::class);
});

it('registers no middleware on the web group', function (): void {
    expect(app(HttpKernelContract::class)->getMiddlewareGroups()['web'])
        ->not->toContain(AddReportingEndpointsHeader::class)
        ->not->toContain(AddNetworkErrorLoggingHeader::class);
});

it('adds no headers until the application registers the middleware', function (): void {
    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeaderMissing('Reporting-Endpoints')
        ->assertHeaderMissing('NEL');
});

it('boots without an http kernel bound', function (): void {
    $application = new Application(base_path());

    expect(function () use ($application): void {
        (new LaravelFordutyServiceProvider($application))->boot();
    })->not->toThrow(BindingResolutionException::class);
});

it('adds the header when the application appends the middleware to the global stack', function (): void {
    $this->appendGlobalMiddleware(AddReportingEndpointsHeader::class);

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/abc123"');
});

it('adds the header to the group the application registers the middleware on', function (): void {
    $this->appendMiddlewareToGroup('web', AddReportingEndpointsHeader::class);

    Route::middleware('api')->get('/forduty-api', fn (): string => 'ok');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/abc123"');

    $this->get('/forduty-api')
        ->assertOk()
        ->assertHeaderMissing('Reporting-Endpoints');
});

it('adds the header to the single route the application registers the middleware on', function (): void {
    Route::middleware(['web', AddReportingEndpointsHeader::class])
        ->get('/forduty-single', fn (): string => 'ok');

    $this->get('/forduty-single')
        ->assertOk()
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/abc123"');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeaderMissing('Reporting-Endpoints');
});

it('honours withoutMiddleware() on a route when registered on a middleware group', function (): void {
    $this->appendMiddlewareToGroup(
        'web',
        AddReportingEndpointsHeader::class,
        AddNetworkErrorLoggingHeader::class,
    );

    Route::middleware('web')
        ->get('/forduty-excluded', fn (): string => 'ok')
        ->withoutMiddleware(AddNetworkErrorLoggingHeader::class);

    $this->get('/forduty-excluded')
        ->assertOk()
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/abc123"')
        ->assertHeaderMissing('NEL');
});

it('cannot be excluded from a route when appended to the global stack', function (): void {
    $this->appendGlobalMiddleware(AddReportingEndpointsHeader::class);

    Route::middleware('web')
        ->get('/forduty-excluded', fn (): string => 'ok')
        ->withoutMiddleware(AddReportingEndpointsHeader::class);

    $this->get('/forduty-excluded')
        ->assertOk()
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/abc123"');
});

it('adds the network error logging policy only when that middleware is registered too', function (): void {
    $this->appendGlobalMiddleware(AddReportingEndpointsHeader::class);

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/abc123"')
        ->assertHeaderMissing('NEL');
});
