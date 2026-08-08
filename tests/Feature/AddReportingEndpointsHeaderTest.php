<?php

declare(strict_types=1);

use Concept7\LaravelForduty\Http\Middleware\AddReportingEndpointsHeader;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;

beforeEach(function (): void {
    $this->appendGlobalMiddleware(AddReportingEndpointsHeader::class);

    Route::middleware('web')->get('/forduty-test', fn (): string => 'ok');
});

it('adds the reporting endpoints header when token and base url are set', function (): void {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', 'https://in.forduty.app');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/abc123"');
});

it('adds no header when the token is missing', function (): void {
    config()->set('laravel-forduty.token', null);
    config()->set('laravel-forduty.base_url', 'https://in.forduty.app');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeaderMissing('Reporting-Endpoints');
});

it('adds no header when the base url is missing', function (): void {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', null);

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeaderMissing('Reporting-Endpoints');
});

it('adds no header when both token and base url are missing', function (): void {
    config()->set('laravel-forduty.token', null);
    config()->set('laravel-forduty.base_url', null);

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeaderMissing('Reporting-Endpoints');
});

it('adds no header when the token is an empty string', function (): void {
    config()->set('laravel-forduty.token', '');
    config()->set('laravel-forduty.base_url', 'https://in.forduty.app');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeaderMissing('Reporting-Endpoints');
});

it('adds no header when the base url is an empty string', function (): void {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', '');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeaderMissing('Reporting-Endpoints');
});

it('normalizes a trailing slash on the base url', function (): void {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', 'https://in.forduty.app/');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/abc123"');
});

it('normalizes a leading slash on the token', function (): void {
    config()->set('laravel-forduty.token', '/abc123');
    config()->set('laravel-forduty.base_url', 'https://in.forduty.app');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/abc123"');
});

it('trims surrounding whitespace off the token and base url', function (): void {
    config()->set('laravel-forduty.token', '  abc123  ');
    config()->set('laravel-forduty.base_url', '  https://in.forduty.app  ');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/abc123"');
});

it('appends the token to a base url that already has a path', function (): void {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', 'https://in.forduty.app/ingest');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/ingest/abc123"');
});

it('adds no header when the base url cannot be parsed', function (): void {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', 'https://in forduty.app');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeaderMissing('Reporting-Endpoints');
});

it('adds no header when the token contains characters a uri cannot hold', function (): void {
    config()->set('laravel-forduty.token', "abc123\r\nX-Injected: 1");
    config()->set('laravel-forduty.base_url', 'https://in.forduty.app');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeaderMissing('Reporting-Endpoints')
        ->assertHeaderMissing('X-Injected');
});

it('adds no header when the base url has no scheme or host', function (): void {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', 'in.forduty.app');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeaderMissing('Reporting-Endpoints');
});

it('adds no header when the base url scheme is not http or https', function (string $baseUrl): void {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', $baseUrl);

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeaderMissing('Reporting-Endpoints');
})->with([
    'ftp://in.forduty.app',
    'wss://in.forduty.app',
]);

it('adds the header for a plain http base url', function (): void {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', 'http://forduty.test');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeader('Reporting-Endpoints', 'default="http://forduty.test/abc123"');
});

it('adds no header when the base url carries credentials', function (): void {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', 'https://user:secret@in.forduty.app');

    $response = $this->get('/forduty-test')->assertOk();

    $response->assertHeaderMissing('Reporting-Endpoints');

    expect($response->headers->all())->not->toContain('secret');
});

it('drops a fragment from the base url', function (): void {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', 'https://in.forduty.app#fragment');

    $this->get('/forduty-test')
        ->assertOk()
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/abc123"');
});

it('overwrites an existing reporting endpoints header', function (): void {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', 'https://in.forduty.app');

    Route::middleware('web')->get('/forduty-existing-header', fn (): Response => response('ok')
        ->header('Reporting-Endpoints', 'default="https://example.com/other"'));

    $this->get('/forduty-existing-header')
        ->assertOk()
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/abc123"');
});

it('adds the header to a request that matches no route', function (): void {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', 'https://in.forduty.app');

    $this->get('/forduty-no-such-route')
        ->assertNotFound()
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/abc123"');
});

it('adds the header to routes outside the web group', function (): void {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', 'https://in.forduty.app');

    Route::middleware('api')->get('/forduty-api', fn (): string => 'ok');

    $this->get('/forduty-api')
        ->assertOk()
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/abc123"');
});

it('adds the header to routes carrying their own middleware stack', function (): void {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', 'https://in.forduty.app');

    Route::middleware([
        EncryptCookies::class,
        StartSession::class,
        SubstituteBindings::class,
    ])->get('/forduty-own-stack', fn (): string => 'ok');

    $this->get('/forduty-own-stack')
        ->assertOk()
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/abc123"');
});

it('adds the header to routes in no group at all', function (): void {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', 'https://in.forduty.app');

    Route::get('/forduty-bare', fn (): string => 'ok');

    $this->get('/forduty-bare')
        ->assertOk()
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/abc123"');
});

it('adds the header to redirect responses', function (): void {
    config()->set('laravel-forduty.token', 'abc123');
    config()->set('laravel-forduty.base_url', 'https://in.forduty.app');

    Route::middleware('web')->get('/forduty-redirect', fn (): RedirectResponse => redirect('/forduty-test'));

    $this->get('/forduty-redirect')
        ->assertRedirect('/forduty-test')
        ->assertHeader('Reporting-Endpoints', 'default="https://in.forduty.app/abc123"');
});
