# Skill: Testing

**Repo:** `muradyanvano/php-lexorank`

## Suites (`phpunit.xml.dist`)

| Suite | Path | Focus | PHP |
|-------|------|-------|-----|
| Unit | `tests/Unit/` | LexoRank, Math, Service, Rebalancer, invariants | 8.1+ |
| Integration | `tests/Integration/` | Performance / larger scenarios | 8.1+ |
| Laravel | `tests/Laravel/` | Testbench, cast, trait, facade | **8.2+** after installing Laravel deps |

## Commands

```bash
composer test:core         # Unit + Integration (PHP 8.1+)
composer test              # all suites (Laravel needs extra deps)
composer test:laravel
composer check             # validate + format + analyse + test:core
composer audit
```

## PHPStan

- `phpstan.neon.dist` — core only (`src/Laravel` excluded); runs on PHP 8.1
- `phpstan-laravel.neon.dist` — full `src/` + Larastan; CI on PHP 8.3 after requiring Laravel

## CI alignment

`.github/workflows/tests.yml`:

- PHP **8.1–8.4**: default require-dev, Unit+Integration, audit
- PHP **8.2–8.4**: require Laravel 12.61.1+ / Testbench 10, Laravel suite, audit

`.github/workflows/static-analysis.yml`:

- Core analyse + format on PHP 8.1
- Laravel analyse on PHP 8.3 with Larastan

## Invariants

1. `strcmp` === `compareTo()` on canonical strings  
2. `between` strictly inside bounds  
3. Adjacent ranks produce fractional midpoints  
4. Parse round-trip canonical form  
5. Rebalance preserves count and strict order in new bucket  
6. Duplicate / unsorted rebalance input throws  

Every bug fix must add a regression test that fails before the fix.
