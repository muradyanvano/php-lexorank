# AGENTS.md

Instructions for coding agents working on `muradyanvano/php-lexorank`.

## Always read first

1. Read `.ai/README.md`.
2. Read every applicable file under `.ai/skills/` before changing code, CI, docs, or dependencies.
3. Treat `src/` as authoritative when skills are stale — then fix the skill in the same change.

## Project identity

- Composer package: `muradyanvano/php-lexorank`
- GitHub repository: `https://github.com/muradyanvano/php-lexorank`
- Namespace: `MuradyanVano\LexoRank`
- Runtime: PHP `^8.1`, **no** Laravel runtime dependency
- Default `require-dev` is PHP **8.1-compatible** (PHPUnit, PHPStan, CS Fixer, Infection, Rector)
- Laravel / Testbench / Larastan are **not** in default `require-dev`; CI and docs install them only on PHP 8.2+

Packagist vendor and GitHub username are both `muradyanvano`. Do not rename the PHP namespace (`MuradyanVano\LexoRank`) when adjusting package or repository metadata.

## Compatibility (must stay truthful)

| Surface | Support |
|---------|---------|
| Core library (consumers + Unit/Integration) | PHP **8.1+** |
| `composer install` / `composer update` in this repo | PHP **8.1+** (default require-dev) |
| Laravel integration tests | Laravel **12.61.1+** via Orchestra Testbench **^10**, PHP **8.2+** |
| Do not claim | Laravel 10/11 as supported |

```bash
# PHP 8.1+ (default)
composer update
composer test:core
composer check

# PHP 8.2+ Laravel suite
composer require --dev laravel/framework:^12.61.1 orchestra/testbench:^10.0 larastan/larastan:^3.0
composer test:laravel
vendor/bin/phpstan analyse -c phpstan-laravel.neon.dist --memory-limit=512M
```

## Security / Composer rules

- Never suppress or ignore security advisories.
- Never set `audit.block-insecure` to `false`.
- Never use `--ignore-platform-reqs` to paper over version conflicts.
- Do not put unpatched Laravel 10/11 into default `require-dev` (breaks `composer audit` on PHP 8.1).
- Plain PHP consumers must install the package without requiring Laravel.

## Quality gates

```bash
composer validate --strict
composer update          # or install when lock is present
composer test:core       # Unit + Integration (PHP 8.1+)
composer analyse         # core only (src/Laravel excluded)
composer format-check
composer audit
composer check
```

Do not claim a command passed unless it was executed successfully.

## Algorithm / architecture constraints

- No PHP `float` rank math; no mandatory BCMath/GMP for core.
- Exact digit-array arithmetic under `src/Math/` (`@internal`).
- Prefer no runtime dependencies for the core.
- Do not replace LexoRank with naive fractional float indexing.

## Change synchronization

Any change that affects behavior, public API, supported versions, CI, commands, or release workflow must update the matching `.ai` skill(s), `README.md` / `docs/*`, `CHANGELOG.md`, and this file when needed.
