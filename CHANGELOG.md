# Release Notes

## [Unreleased](https://github.com/concept7/laravel-forduty/compare/v0.2.0...HEAD)

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
