<div align="center">
    <h1>Laravel Forduty</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/concept7/laravel-forduty"><img src="https://img.shields.io/packagist/v/concept7/laravel-forduty.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/concept7/laravel-forduty"><img src="https://img.shields.io/packagist/php-v/concept7/laravel-forduty.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/concept7/laravel-forduty"><img src="https://badge.laravel.cloud/badge/concept7/laravel-forduty?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/concept7/laravel-forduty/actions"><img alt="GitHub Workflow Status" src="https://img.shields.io/github/actions/workflow/status/concept7/laravel-forduty/tests.yml?label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/concept7/laravel-forduty"><img src="https://img.shields.io/packagist/dt/concept7/laravel-forduty.svg?style=flat-square" alt="Total Downloads"></a>
</p>

for.duty collects your sites' browser reports — CSP violations, network errors, deprecations — groups them into distinct problems, and alerts your team only when something new appears.

## Installation

You can install the package via Composer:

```bash
composer require concept7/laravel-forduty
```

You may publish all of the package's resources at once:

```bash
php artisan vendor:publish --tag="laravel-forduty"
```

Or, you may publish each resource individually:

### Publishing the Configuration File

```bash
php artisan vendor:publish --tag="laravel-forduty-config"
```

## Usage

Add your for.duty site token to your `.env` file:

```dotenv
FORDUTY_TOKEN=your-site-token
```

Then register the middleware yourself. The package ships it but does not attach it anywhere, so your application decides which responses carry the header.

### Registering the Middleware

Append `AddReportingEndpointsHeader` to the global middleware stack in `bootstrap/app.php`:

```php
use Concept7\LaravelForduty\Http\Middleware\AddReportingEndpointsHeader;

->withMiddleware(function (Middleware $middleware): void {
    $middleware->append(AddReportingEndpointsHeader::class);
})
```

Every response your application returns then carries a `Reporting-Endpoints` header pointing browsers at your for.duty endpoint:

```
Reporting-Endpoints: default="https://in.forduty.app/your-site-token"
```

The global stack is the recommendation for a reason: a browser only delivers a report to an endpoint group it has been given on that same response, so anything the header misses reports nothing. Appending globally covers routes in the `api` group, routes that bring their own middleware list — a Filament panel, for one — and requests that match no route at all. Responses your application never sees, such as static files served by the web server, are beyond reach either way.

To limit the header to one middleware group instead:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->web(append: [AddReportingEndpointsHeader::class]);
})
```

Or to a single route:

```php
Route::get('/checkout', CheckoutController::class)
    ->middleware(AddReportingEndpointsHeader::class);
```

### Behavior

The middleware adds no header when the token is missing or blank, or when the base URL is blank, unparseable, not an absolute `http` or `https` URL, or carries credentials. That keeps a misconfiguration from breaking responses, and makes local and development environments quiet by default. The reporting URL defaults to `https://in.forduty.app` and can be overridden with `FORDUTY_BASE_URL`.

The middleware overwrites any existing `Reporting-Endpoints` header. To keep it off specific routes, register it on the `web` group rather than globally and exclude those routes with `withoutMiddleware()`:

```php
use Concept7\LaravelForduty\Http\Middleware\AddReportingEndpointsHeader;

Route::get('/embed', EmbedController::class)
    ->withoutMiddleware(AddReportingEndpointsHeader::class);
```

`withoutMiddleware()` only reaches middleware the router gathers, so it has no effect on a middleware appended to the global stack. Route-level exclusions and a global registration are a choice between the two.

The package ships a second middleware, `AddNetworkErrorLoggingHeader`, which you register the same way once you opt in — see [Network Error Logging](#network-error-logging).

### Opting Report Types In

`Reporting-Endpoints` only names the endpoints a browser is allowed to deliver reports to. Which reports actually get sent depends on the report type:

- **Deprecations, interventions and crashes** are delivered to the `default` endpoint on their own. The header above is all they need.
- **CSP violations** are only reported once your `Content-Security-Policy` header points at the endpoint with a `report-to` directive:

  ```
  Content-Security-Policy: default-src 'self'; report-to default
  ```

- **Network errors** require an additional `NEL` header, which this package can send for you — see [Network Error Logging](#network-error-logging).

CSP reporting is the one this package cannot turn on for you, because the directive belongs to a header it does not own. If you build your policy with a package such as [spatie/laravel-csp](https://github.com/spatie/laravel-csp), add the `report-to default` directive to it.

### Network Error Logging

Network Error Logging asks the browser to report requests that failed before your application ever saw them — DNS failures, TCP resets, TLS errors, aborted connections. It is off by default. Enable it with:

```dotenv
FORDUTY_NEL_ENABLED=true
```

Then register `AddNetworkErrorLoggingHeader` alongside the other middleware:

```php
use Concept7\LaravelForduty\Http\Middleware\AddNetworkErrorLoggingHeader;
use Concept7\LaravelForduty\Http\Middleware\AddReportingEndpointsHeader;

->withMiddleware(function (Middleware $middleware): void {
    $middleware->append([
        AddReportingEndpointsHeader::class,
        AddNetworkErrorLoggingHeader::class,
    ]);
})
```

Responses then carry a policy alongside the endpoints header:

```
NEL: {"report_to":"default","max_age":2592000,"include_subdomains":false,"success_fraction":0,"failure_fraction":1}
```

Four optional variables tune it:

| Variable | Default | Meaning |
| --- | --- | --- |
| `FORDUTY_NEL_MAX_AGE` | `2592000` | How long, in seconds, the browser keeps the policy. `0` clears a policy it already holds. |
| `FORDUTY_NEL_INCLUDE_SUBDOMAINS` | `false` | Whether the policy also covers subdomains. |
| `FORDUTY_NEL_SUCCESS_FRACTION` | `0.0` | Fraction of *successful* requests to report. Raise this only deliberately — at `1.0` the browser reports every request your site makes. |
| `FORDUTY_NEL_FAILURE_FRACTION` | `1.0` | Fraction of *failed* requests to report. Lower this to sample on a high-traffic site. |

Two things to know:

- **Browsers only honour `NEL` over HTTPS.** Over plain `http` the header is sent and ignored, so local development stays quiet on its own.
- **The policy needs `Reporting-Endpoints` on the same responses.** `report_to` names the `default` group that the other middleware declares, so registering this one on its own — or excluding `AddReportingEndpointsHeader` from a route that keeps this one — leaves the browser with a policy it cannot deliver to.

As with the endpoints header, a value a browser would reject — a negative or fractional `max_age`, a sampling fraction outside `0.0`–`1.0` — means no header at all rather than a silently corrected one. Keeping the policy off specific routes works the same way as for the endpoints header: register on a group, then exclude the route.

```php
use Concept7\LaravelForduty\Http\Middleware\AddNetworkErrorLoggingHeader;

Route::get('/embed', EmbedController::class)
    ->withoutMiddleware(AddNetworkErrorLoggingHeader::class);
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Laravel Forduty! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Jan Henk Hazelaar](https://github.com/concept7)
- [All Contributors](../../contributors)

## License

Laravel Forduty is open-sourced software licensed under the [MIT license](LICENSE.md).
