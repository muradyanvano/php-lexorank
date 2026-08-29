# Skill: PHP code quality

**Repo:** `muradyanvano/php-lexorank`

## Tooling

| Tool | Config | Command |
|------|--------|---------|
| PHP CS Fixer | `.php-cs-fixer.dist.php` | `composer format` / `composer format-check` |
| PHPStan 9 | `phpstan.neon.dist` | `composer analyse` |
| PHPUnit 10–11 | `phpunit.xml.dist` | `composer test` |
| Rector | `rector.php` | `composer rector-check` |
| Infection | `infection.json.dist` | `composer infection` |

## Full gate

```bash
composer check
# = validate-package + format-check + analyse + test
```

**Required before merge:** `composer check` passes.

## PHPStan

- Level **9** on `src/`
- Excludes `src/Laravel/config`
- Includes strict rules + Larastan + phpunit extension
- Laravel code must type-hint against Illuminate contracts correctly

## Code style

- `declare(strict_types=1);` mandatory
- PSR-12 + ordered imports + single quotes + trailing commas in multiline
- `final` on value objects and services where applicable
- Yoda style **disabled**

## Structure conventions

- Exceptions: static factory methods like `InvalidRankFormatException::malformed($rank)`
- Value objects: private constructor, static factories
- `@internal` on Math namespace and internal LexoRank helpers
- `@throws` in docblocks on public service methods where applicable

## Do not

- Add runtime deps beyond PHP ^8.1 without explicit approval
- Expose `Math\*` as public API
- Lower PHPStan level to silence errors — fix types instead
- Skip tests for behavior changes

## PHP version

Target `^8.1`. CI runs 8.1, 8.2, 8.3, 8.4.

Use modern PHP (match, typed properties, readonly where appropriate) compatible with 8.1.
