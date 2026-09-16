# Skill: Package architecture

**Repo:** `muradyanvano/php-lexorank`  
**Namespace:** `MuradyanVano\LexoRank`

## Layer map

```
Laravel/          → optional: ServiceProvider, Cast, HasLexoRank, Facade (alias LexoRankFacade)
LexoRankService     → orchestration, bulk ops, max length
Rebalancer          → rebalance() + shouldRebalance()
LexoRankCollection  → array helpers
LexoRank              → value object: parse, between, before, after
LexoRankBucket        → 0|1|2 rotation
Math/*                → @internal — LexoDecimal, LexoInteger, NumeralSystem36
Exception/*           → typed failures
```

## Public classes (semver)

| Class | File |
|-------|------|
| `LexoRank` | `src/LexoRank.php` |
| `LexoRankBucket` | `src/LexoRankBucket.php` |
| `LexoRankService` | `src/LexoRankService.php` |
| `LexoRankCollection` | `src/LexoRankCollection.php` |
| `Rebalancer` | `src/Rebalancer.php` |
| `RebalanceResult` | `src/RebalanceResult.php` |
| `Laravel\*` | `src/Laravel/` |

## Not public API

- `MuradyanVano\LexoRank\Math\*` — digit arithmetic (`@internal` on types)
- `LexoRank::decimal()`, `LexoRank::betweenDecimals()`, `LexoRank::from()` — `@internal` (still public methods for BC; do not call from apps)

## Identity

| Surface | Value |
|---------|-------|
| Composer package | `muradyanvano/php-lexorank` |
| GitHub repository | `https://github.com/muradyanvano/php-lexorank` |

Keep `composer.json` `homepage` / `support` on the `muradyanvano/php-lexorank` GitHub path. Do not rename the PHP namespace when updating package or repository metadata.

## Design constraints

1. **Immutable** `LexoRank` — every operation returns new instance
2. **No DB I/O** in core or rebalancer — mapping only
3. **No floats** for rank math
4. **`between()` same bucket only** — cross-bucket = rebalance
5. **Canonical strings** — always persist `toString()` after parse

## Laravel registration

`composer.json` extra.laravel:

- Provider: `MuradyanVano\LexoRank\Laravel\LexoRankServiceProvider`
- Alias: `LexoRankFacade` → `MuradyanVano\LexoRank\Laravel\Facades\LexoRank`

Singletons: `LexoRankService`, `Rebalancer`.

## Compatibility

Atlassian LexoRank **inspired**, not cloned. Do not assume interop without tests. See `docs/architecture.md`.

## When editing

- New public methods → update README §8–11, relevant docs, this skill
- Internal math changes → update `lexorank-algorithm.md`, `arbitrary-precision-math.md`
- Laravel surface → update `laravel-integration.md`, `docs/laravel.md`
