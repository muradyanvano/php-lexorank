# Skill: Testing

**Repo:** `muradyanvano/php-lexorank`

## Suites (`phpunit.xml.dist`)

| Suite | Path | Focus | PHP |
|-------|------|-------|-----|
| Unit | `tests/Unit/` | LexoRank, Math, Service, Rebalancer, invariants | 8.1+ |
| Integration | `tests/Integration/` | Performance / larger scenarios | 8.1+ |
| Laravel | `tests/Laravel/` | Testbench, cast, trait, facade | **8.2+** (Laravel 12.61.1+) |

## Commands

```bash
composer test              # all suites (Laravel needs PHP 8.2+)
composer test:unit
composer test:integration
composer test:laravel
composer audit             # must report no known vulnerabilities
```

## Key test files

| File | Covers |
|------|--------|
| `LexoRankTest.php` | parse, between, before/after, buckets, strcmp invariant |
| `InvariantTest.php` | broader ordering invariants |
| `MathTest.php` | internal decimal arithmetic |
| `ServiceAndRebalancerTest.php` | betweenMany, rebalance, duplicates |
| `LaravelIntegrationTest.php` | container, cast, trait, facade |
| `PerformanceTest.php` | integration performance |

## Invariants to preserve

1. `strcmp` === `compareTo()` on canonical strings
2. `between` strictly inside bounds
3. Adjacent ranks produce fractional midpoints
4. Parse round-trip canonical form
5. Rebalance preserves count and strict order in new bucket
6. Duplicate / unsorted rebalance input throws

## Writing new tests

- Namespace: `MuradyanVano\LexoRank\Tests\...`
- Use `final class ...Test extends TestCase`
- Prefer data from actual API (`LexoRank::parse('0|100000:')`) not invented ranks
- Exception tests: `expectException` with specific exception class
- Every bug fix must add a regression test that fails before the fix

## Laravel tests

Extend `Orchestra\Testbench\TestCase`:

- `getPackageProviders()` → `[LexoRankServiceProvider::class]`
- `getPackageAliases()` → `LexoRankFacade`
- Define migrations in `defineDatabaseMigrations()`

Require-dev lockset: `laravel/framework ^12.61.1`, `orchestra/testbench ^10.0`.

## Mutation testing

```bash
composer infection
```

Thresholds in `infection.json.dist` (min MSI 70%).

## CI alignment

`.github/workflows/tests.yml`:

- PHP **8.1**: strip Laravel/Testbench/Larastan, pin PHPUnit 10, run Unit+Integration
- PHP **8.2–8.4**: full install, Unit+Integration; separate Laravel job runs Laravel suite + `composer audit`
- Static analysis job (PHP 8.3): format-check, PHPStan, audit

After adding tests, run locally (on PHP 8.2+ for full suite):

```bash
composer check
composer audit
```
