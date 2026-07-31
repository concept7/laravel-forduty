<div align="center">
    <h1>Laravel Forduty</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/concept7/laravel-forduty"><img src="https://img.shields.io/packagist/v/concept7/laravel-forduty.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/concept7/laravel-forduty"><img src="https://img.shields.io/packagist/php-v/concept7/laravel-forduty.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/concept7/laravel-forduty"><img src="https://badge.laravel.cloud/badge/concept7/laravel-forduty?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/concept7/laravel-forduty/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/concept7/laravel-forduty/tests.yml?branch=main&label=Tests&style=flat-square"></a>
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

That's it. The package automatically appends its middleware to the `web` middleware group, and every response will include a `Reporting-Endpoints` header pointing browsers at your for.duty endpoint:

```
Reporting-Endpoints: default="https://in.forduty.app/your-site-token"
```

When the token is missing or blank, the middleware adds no header — a safe default for local and development environments. The reporting URL defaults to `https://in.forduty.app` and can be overridden with `FORDUTY_BASE_URL`.

The middleware overwrites any existing `Reporting-Endpoints` header. To disable it for specific routes, use `withoutMiddleware()`:

```php
use Concept7\LaravelForduty\Http\Middleware\AddReportingEndpointsHeader;

Route::get('/embed', EmbedController::class)
    ->withoutMiddleware(AddReportingEndpointsHeader::class);
```

To attach it to other middleware groups such as `api`, append it in `bootstrap/app.php`:

```php
use Concept7\LaravelForduty\Http\Middleware\AddReportingEndpointsHeader;

->withMiddleware(function (Middleware $middleware): void {
    $middleware->api(append: AddReportingEndpointsHeader::class);
})
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
