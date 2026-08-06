<?php

declare(strict_types=1);

use Concept7\LaravelForduty\Http\Middleware\AddReportingEndpointsHeader;
use Concept7\LaravelForduty\LaravelFordutyServiceProvider;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Foundation\Application;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route;

beforeEach(function () {
    Route::middleware('web')->get('/forduty-test', fn (): string => 'ok');
});

it('attaches the middleware to the web group', function () {
    expect(app(Router::class)->getMiddlewareGroups()['web'])
        ->toContain(AddReportingEndpointsHeader::class);
});

it('keeps middleware another provider pushed onto the router web group', function () {
    Route::pushMiddlewareToGroup('web', 'Other\\Package\\Middleware');

    (new LaravelFordutyServiceProvider(app()))->boot();

    expect(app(Router::class)->getMiddlewareGroups()['web'])
        ->toContain('Other\\Package\\Middleware')
        ->toContain(AddReportingEndpointsHeader::class);
});

it('boots without an http kernel bound', function () {
    $application = new Application(base_path());

    expect(fn () => (new LaravelFordutyServiceProvider($application))->boot())
        ->not->toThrow(BindingResolutionException::class);
});

it('adds the reporting endpoints header when token and base url are set', function () {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', 'https://in.forduty.app');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/abc123"');
});

it('adds no header when the token is missing', function () {
    config()->set('laravel-forduty.token', null);
    config()->set('laravel-forduty.base_url', 'https://in.forduty.app');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeaderMissing('Reporting-Endpoints');
});

it('adds no header when the base url is missing', function () {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', null);

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeaderMissing('Reporting-Endpoints');
});

it('adds no header when both token and base url are missing', function () {
    config()->set('laravel-forduty.token', null);
    config()->set('laravel-forduty.base_url', null);

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeaderMissing('Reporting-Endpoints');
});

it('adds no header when the token is an empty string', function () {
    config()->set('laravel-forduty.token', '');
    config()->set('laravel-forduty.base_url', 'https://in.forduty.app');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeaderMissing('Reporting-Endpoints');
});

it('adds no header when the base url is an empty string', function () {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', '');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeaderMissing('Reporting-Endpoints');
});

it('normalizes a trailing slash on the base url', function () {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', 'https://in.forduty.app/');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/abc123"');
});

it('normalizes a leading slash on the token', function () {
    config()->set('laravel-forduty.token', '/abc123');
    config()->set('laravel-forduty.base_url', 'https://in.forduty.app');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/abc123"');
});

it('trims surrounding whitespace off the token and base url', function () {
    config()->set('laravel-forduty.token', '  abc123  ');
    config()->set('laravel-forduty.base_url', '  https://in.forduty.app  ');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/abc123"');
});

it('appends the token to a base url that already has a path', function () {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', 'https://in.forduty.app/ingest');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/ingest/abc123"');
});

it('adds no header when the base url cannot be parsed', function () {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', 'https://in forduty.app');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeaderMissing('Reporting-Endpoints');
});

it('adds no header when the token contains characters a uri cannot hold', function () {
    config()->set('laravel-forduty.token', "abc123\r\nX-Injected: 1");
    config()->set('laravel-forduty.base_url', 'https://in.forduty.app');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeaderMissing('Reporting-Endpoints')
        ->assertHeaderMissing('X-Injected');
});

it('adds no header when the base url has no scheme or host', function () {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', 'in.forduty.app');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeaderMissing('Reporting-Endpoints');
});

it('overwrites an existing reporting endpoints header', function () {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', 'https://in.forduty.app');

    Route::middleware('web')->get('/forduty-existing-header', fn () => response('ok')
        ->header('Reporting-Endpoints', 'default="https://example.com/other"'));

    $this->get('/forduty-existing-header')
        ->assertOk()
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/abc123"');
});

it('adds the header to redirect responses', function () {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', 'https://in.forduty.app');

    Route::middleware('web')->get('/forduty-redirect', fn () => redirect('/forduty-test'));

    $this->get('/forduty-redirect')
        ->assertRedirect('/forduty-test')
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/abc123"');
});
