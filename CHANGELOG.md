# Release Notes

## [Unreleased](https://github.com/concept7/laravel-forduty/compare/v0.1.1...HEAD)

## [v0.1.1](https://github.com/concept7/laravel-forduty/compare/v0.1.0...v0.1.1) - 2026-07-31

### Changed

- The reporting base URL now defaults to `https://in.forduty.app`, so only `FORDUTY_TOKEN` needs to be set. `FORDUTY_BASE_URL` still overrides the default when set.

## [v0.1.0](https://github.com/concept7/laravel-forduty/releases/tag/v0.1.0) - 2026-07-31

Initial pre-release.

### Added

- `Reporting-Endpoints` middleware that points browsers at your for.duty ingest endpoint, automatically appended to the `web` middleware group and configured via `FORDUTY_BASE_URL` and `FORDUTY_TOKEN`.
