# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.3] - 2026-09-17

### Changed

- Migrated GitHub repository and metadata links from `muradyanvano1995/php-lexorank` to `muradyanvano/php-lexorank` (Composer package name `muradyanvano/php-lexorank` unchanged).
- Updated demo application links to `muradyanvano/php-lexorank-demo-app`.

## [0.1.2] - 2026-08-30

### Added

- Linked the full Laravel 13 and React 19 LexoRank demo application.

## [0.1.1] - 2026-08-29

### Fixed

- Corrected GitHub repository, issue, clone, release, and comparison links to `muradyanvano1995/php-lexorank` (Composer package name `muradyanvano/php-lexorank` unchanged).
- Marked `LexoRank::from()` consistently as `@internal` API (method remains public; applications must not depend on `Math\LexoDecimal`).
- Added PHPUnit regression coverage for Composer repository metadata (`homepage` / `support`).

### Changed

- Default `require-dev` no longer includes Laravel/Testbench/Larastan, so **`composer update` works on PHP 8.1** while core remains `php: ^8.1`.
- Laravel 12.61.1+ / Testbench 10 are installed only in Laravel CI jobs (and optionally by contributors on PHP 8.2+).
- Raised Laravel integration test floor to **Laravel 12.61.1+** / Orchestra Testbench **^10** (PHP 8.2+) to clear `composer audit` advisories that remain open on Laravel 10/11.
- Removed Composer `audit.block-insecure: false`; CI now fails on known vulnerabilities.
- Split PHPStan configs: `phpstan.neon.dist` (core) and `phpstan-laravel.neon.dist` (Laravel + Larastan).
- Added root `AGENTS.md` and `composer test:core` / `composer check` targeting PHP 8.1-friendly workflows.

## [0.1.0] - TBD

### Added

- Initial release of `muradyanvano/php-lexorank`
- `LexoRank` and `LexoRankBucket` value objects
- `LexoRankService` for bulk allocation and duplicate detection
- `Rebalancer` and `RebalanceResult` for bucket migration
- `LexoRankCollection` helpers
- Internal `Math\*` digit-array arithmetic (base 36)
- Laravel integration: service provider, cast, trait, facade (`LexoRankFacade`)
- Exception hierarchy under `MuradyanVano\LexoRank\Exception\`
- PHPUnit test suites (Unit, Integration, Laravel)
- PHPStan level 9, PHP CS Fixer, Infection configuration

[Unreleased]: https://github.com/muradyanvano/php-lexorank/compare/v0.1.3...HEAD
[0.1.3]: https://github.com/muradyanvano/php-lexorank/compare/v0.1.2...v0.1.3
[0.1.2]: https://github.com/muradyanvano/php-lexorank/compare/v0.1.1...v0.1.2
[0.1.1]: https://github.com/muradyanvano/php-lexorank/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/muradyanvano/php-lexorank/releases/tag/v0.1.0
