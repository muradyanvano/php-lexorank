# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- Default `require-dev` no longer includes Laravel/Testbench/Larastan, so **`composer update` works on PHP 8.1** while core remains `php: ^8.1`.
- Laravel 12.61.1+ / Testbench 10 are installed only in Laravel CI jobs (and optionally by contributors on PHP 8.2+).
- Raised Laravel integration test floor to **Laravel 12.61.1+** / Orchestra Testbench **^10** (PHP 8.2+) to clear `composer audit` advisories that remain open on Laravel 10/11.
- Removed Composer `audit.block-insecure: false`; CI now fails on known vulnerabilities.
- Split PHPStan configs: `phpstan.neon.dist` (core) and `phpstan-laravel.neon.dist` (Laravel + Larastan).
- Added root `AGENTS.md` and `composer test:core` / `composer check` targeting PHP 8.1-friendly workflows.

### Added

- (Nothing yet)

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

[Unreleased]: https://github.com/muradyanvano/php-lexorank/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/muradyanvano/php-lexorank/releases/tag/v0.1.0
