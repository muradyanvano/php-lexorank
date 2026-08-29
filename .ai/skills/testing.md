# Skill: Testing

**Repo:** `muradyanvano/php-lexorank`

## Suites (`phpunit.xml.dist`)

| Suite | Path | Focus |
|-------|------|-------|
| Unit | `tests/Unit/` | LexoRank, Math, Service, Rebalancer, invariants |
| Integration | `tests/Integration/` | Performance / larger scenarios |
| Laravel | `tests/Laravel/` | Testbench, cast, trait, facade |

## Commands

```bash
composer test              # all suites
composer test:unit
composer test:integration
composer test:laravel
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

## Laravel tests

Extend `Orchestra\Testbench\TestCase`:

- `getPackageProviders()` → `[LexoRankServiceProvider::class]`
- `getPackageAliases()` → `LexoRankFacade`
- Define migrations in `defineDatabaseMigrations()`

## Mutation testing

```bash
composer infection
```

Thresholds in `infection.json.dist` (min MSI 70%).

## CI alignment

GitHub Actions runs PHPUnit on PHP 8.1–8.4 and Laravel via Testbench matrix. See `.github/workflows/tests.yml`.

After adding tests, run locally:

```bash
composer check
```
