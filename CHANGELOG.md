# Release Notes

## [Unreleased](https://github.com/concept7/laravel-forduty/compare/v0.4.0...HEAD)

### Breaking Changes

- The package no longer registers its middleware. Applications append `AddReportingEndpointsHeader` — and `AddNetworkErrorLoggingHeader`, if network error logging is on — to their own middleware stack in `bootstrap/app.php`:

  ```php
  use Concept7\LaravelForduty\Http\Middleware\AddReportingEndpointsHeader;

  ->withMiddleware(function (Middleware $middleware): void {
      $middleware->append(AddReportingEndpointsHeader::class);
  })
  ```

  Upgrading without that leaves responses with no headers at all, and nothing arriving at for.duty. Which responses carry a header is the application's call, and a package that pushes middleware onto the global stack from a service provider takes that call away — invisibly, from a file nobody looks at when a header shows up where it was not wanted. The README recommends the global stack, and explains what a narrower registration gives up, but the registration itself is now yours to write.

### Removed

- The `RespectsRouteExclusions` trait, which made `withoutMiddleware()` work on middleware the router never gathered. It existed to paper over the global registration the package did for you; with the registration in the application's hands, plain Laravel rules apply again — `withoutMiddleware()` reaches group and route middleware, and not the global stack. An application that wants per-route exclusions registers on a group instead of globally.

## [v0.4.0](https://github.com/concept7/laravel-forduty/compare/v0.3.0...v0.4.0) - 2026-08-07

### Fixed

- Both middlewares now run on the global middleware stack instead of the `web` group, so every response carries the headers.

  Registering on the `web` group quietly excluded anything that brings its own middleware list, a Filament panel most of all. On those pages a `Content-Security-Policy` added globally still ended in `report-to default`, but the `Reporting-Endpoints` header naming that group never arrived, and a browser handed a group it has no endpoint for logs the violation to the console and throws the report away. An application could run for weeks with a correct policy, visible violations, and nothing at all coming in.

  Requests that match no route — 404s — and routes in groups such as `api` are covered now as well. Responses the application never sees, such as static files served by the web server, still are not.

### Changed

- Per-route `withoutMiddleware()` keeps working, even though global middleware is normally beyond its reach: both middlewares check the matched route for their own exclusion before writing a header. The documented opt-out is unchanged.
- The README section on attaching the middleware to other middleware groups is gone, along with the need for it.

## [v0.3.0](https://github.com/concept7/laravel-forduty/compare/v0.2.1...v0.3.0) - 2026-08-07

### Added

- An optional `NEL` header, which asks browsers to report requests that failed before your application saw them — DNS failures, TCP resets, TLS errors, aborted connections. It is off by default; set `FORDUTY_NEL_ENABLED=true` to turn it on. `FORDUTY_NEL_MAX_AGE`, `FORDUTY_NEL_INCLUDE_SUBDOMAINS`, `FORDUTY_NEL_SUCCESS_FRACTION` and `FORDUTY_NEL_FAILURE_FRACTION` tune the policy.

  The header comes from a separate `AddNetworkErrorLoggingHeader` middleware, so each header can be excluded per route with `withoutMiddleware()`. A NEL policy is inert without an endpoint to name, so it is only emitted when one resolves. As elsewhere in the package, a value a browser would reject means no header rather than a corrected one.

  Note that browsers only honour `NEL` over HTTPS, and the policy needs `Reporting-Endpoints` on the same responses.

### Changed

- Endpoint resolution moved from `AddReportingEndpointsHeader` into the `LaravelForduty` singleton, which both middlewares now share. The `Reporting-Endpoints` header behaves exactly as before.

## [v0.2.1](https://github.com/concept7/laravel-forduty/compare/v0.2.0...v0.2.1) - 2026-08-07

### Fixed

- v0.2.0 emitted no `Reporting-Endpoints` header at all on Laravel 12. The endpoint was built with `Illuminate\Support\Uri::withoutFragment()`, which only exists in Laravel 13; on Laravel 12 the call reached `Macroable::__call` and threw, and because the endpoint is built inside `rescue(..., report: false)` the exception was swallowed. The result was a package that silently did nothing, with no log line explaining why.

### Changed

- The package now requires Laravel 13. Laravel 12 is no longer supported, which makes the declared support matrix honest — it never worked there. `orchestra/testbench` follows to `^11.0`. PHP requirements are unchanged at `^8.3`.

## [v0.2.0](https://github.com/concept7/laravel-forduty/compare/v0.1.1...v0.2.0) - 2026-08-06

### Breaking Changes

- The package now requires `laravel/framework` instead of `illuminate/support`. The middleware and service provider depend on `Illuminate\Http`, `Symfony\Component\HttpFoundation` and `Illuminate\Foundation\Http\Kernel`, which `illuminate/support` does not provide. Applications built on individual `illuminate/*` components rather than the full framework are no longer supported.

### Fixed

- A malformed `FORDUTY_BASE_URL`, or a token containing characters a URI cannot hold, no longer throws out of the `web` middleware group. Either previously returned HTTP 500 on every request.
- Surrounding whitespace on the token and base URL is trimmed before the endpoint is built, instead of being percent-encoded into it and silently losing reports.
- A blank `FORDUTY_BASE_URL` falls back to the default instead of disabling the header entirely.
- The service provider no longer throws when no HTTP kernel is bound, so console-only applications boot.
- Middleware another service provider pushed directly onto the router's `web` group is preserved instead of discarded.
- Base URLs browsers cannot deliver reports to — a non-`http`/`https` scheme, a missing host, or embedded credentials — are treated as unconfigured. A fragment is dropped.

### Documentation

- Clarified that the header covers the `web` group only: requests that match no route, and routes in other groups, are served without it.
- Documented that `Reporting-Endpoints` alone does not enable CSP reporting; that needs a `report-to` directive in your `Content-Security-Policy` header.

### Maintenance

- Release automation and the tests badge no longer hardcode the default branch name.

## [v0.1.1](https://github.com/concept7/laravel-forduty/compare/v0.1.0...v0.1.1) - 2026-07-31

### Changed

- The reporting base URL now defaults to `https://in.forduty.app`, so only `FORDUTY_TOKEN` needs to be set. `FORDUTY_BASE_URL` still overrides the default when set.

## [v0.1.0](https://github.com/concept7/laravel-forduty/releases/tag/v0.1.0) - 2026-07-31

Initial pre-release.

### Added

- `Reporting-Endpoints` middleware that points browsers at your for.duty ingest endpoint, automatically appended to the `web` middleware group and configured via `FORDUTY_BASE_URL` and `FORDUTY_TOKEN`.
