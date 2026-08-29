# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- Raised Laravel integration test floor to **Laravel 12.61.1+** / Orchestra Testbench **^10** (PHP 8.2+) to clear `composer audit` advisories that remain open on Laravel 10/11.
- Removed Composer `audit.block-insecure: false`; CI now fails on known vulnerabilities.
- Documented truthful matrix: PHP 8.1 runs core tests only; Laravel suite requires PHP 8.2+.
- Added root `AGENTS.md` for coding agents.

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
