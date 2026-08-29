# Architecture

This document describes the design of **muradyanvano/php-lexorank** (`MuradyanVano\LexoRank`): what is public, what is internal, and how the pieces fit together.

## Goals

- **Deterministic ordering** — ranks compare correctly with `strcmp` on canonical strings and with `LexoRank::compareTo()`.
- **O(1) inserts** — new positions are computed from neighbours without renumbering the whole list.
- **No floating-point math** — all arithmetic uses digit arrays in base 36; no `float`, BCMath, or GMP.
- **Framework-optional core** — the ranking engine works in plain PHP; Laravel integration is an optional layer.
- **Truthful Laravel matrix** — require-dev verifies Laravel **12.61.1+** (Testbench ^10) on PHP **8.2+**; core remains PHP **8.1+**.
- **Explicit persistence** — rebalancing returns a mapping; the library never writes to a database.

## Layer overview

```
Application / Laravel models
        │
        ▼
LexoRankService, Rebalancer, LexoRankCollection  ← orchestration
        │
        ▼
LexoRank, LexoRankBucket                           ← value objects
        │
        ▼
Math\LexoDecimal, LexoInteger, NumeralSystem36     ← @internal arithmetic
        │
        ▼
Exception\*                                        ← typed failures
```

## Public API surface

### Value objects

| Class | Role |
|-------|------|
| `LexoRank` | Immutable rank: parse, generate (`between`, `before`, `after`), compare, bucket moves |
| `LexoRankBucket` | Rotating bucket `0` / `1` / `2` for rebalance migrations |

### Orchestration

| Class | Role |
|-------|------|
| `LexoRankService` | Bounded helpers: nullable `between()`, bulk `betweenMany()` / `initialRanks()`, duplicate checks, max length enforcement |
| `Rebalancer` | Produce evenly spaced ranks in the next bucket plus an old→new mapping |
| `RebalanceResult` | Bucket, new ranks, mapping, `map()` lookup |
| `LexoRankCollection` | Sorting, duplicate detection, insert-index helper, rebalance hints |

### Laravel (optional)

| Class | Role |
|-------|------|
| `Laravel\LexoRankServiceProvider` | Registers `LexoRankService` and `Rebalancer` singletons |
| `Laravel\Casts\LexoRankCast` | Eloquent attribute cast |
| `Laravel\Concerns\HasLexoRank` | Model helpers (`moveBefore`, `moveAfter`, `moveBetween`, `*AndSave`, `scopeOrderByRank`) |
| `Laravel\Facades\LexoRank` | Facade for `LexoRankService`; alias **`LexoRankFacade`** in `composer.json` |

Configuration lives in `src/Laravel/config/lexorank.php` (`max_count`, `max_rank_length`, `column`).

## Internal API (`@internal`)

The `MuradyanVano\LexoRank\Math\*` namespace is **not part of the semver public contract**:

- `NumeralSystem36` — alphabet, radix, integer width (6), step (8), default max length (255)
- `LexoInteger` — arbitrary-precision integer in base 36
- `LexoDecimal` — fixed-scale decimal with `:` radix; add, subtract, multiply, compare, scale expansion

`LexoRank::decimal()` and `LexoRank::betweenDecimals()` are marked `@internal`. Application code should use `LexoRank` and `LexoRankService` only.

`LexoRank::from(LexoRankBucket, LexoDecimal)` exists for internal composition; prefer `parse()` / factory methods in application code.

## Design decisions

### Canonical serialization

Every rank serializes as `{bucket}|{decimal}` where the decimal part is:

- Integer segment zero-padded to **6** base-36 digits
- Radix point `:` always present
- Trailing fractional zeros stripped

Example: `0|hzzzzz:` (middle of bucket 0).

Non-canonical input that is semantically valid is normalized on parse (unpadded integers, trailing zeros).

### Bucket rotation

Three buckets rotate `0 → 1 → 2 → 0`. During rebalance, new ranks are written into the **next** bucket while old ranks remain readable until the transaction commits. Lexicographic order is preserved **within** a bucket; cross-bucket ordering is a migration concern, not insert-time math.

### `between()` vs `LexoRankService::between()`

- `LexoRank::between($other)` requires the **same bucket** and throws on equal bounds.
- `LexoRankService::between(?$lower, ?$upper)` accepts open ends (`null` = min/max side) and enforces `max_rank_length`.

### Bulk allocation

`betweenMany()` uses recursive midpoint partitioning for even spacing, not repeated end inserts into a shrinking gap.

### Length limits

Default max serialized length: **255** (`NumeralSystem36::DEFAULT_MAX_RANK_LENGTH`). `LexoRankService` throws `RankSpaceExhaustedException` when a generated rank exceeds the configured limit. Rebalance when ranks approach a soft threshold (default **64** in `Rebalancer::shouldRebalance()` / `LexoRankCollection::suggestsRebalance()`).

## Compatibility boundary

This package is **inspired by** Atlassian LexoRank and reference implementations (e.g. kvandake). It is **not** a byte-for-byte clone.

| Compatible in spirit | Not guaranteed |
|---------------------|----------------|
| Base-36 lowercase alphabet `0-9a-z` | Identical midpoint strings for every edge case vs Jira |
| `{bucket}\|{padded-int}:{fraction}` shape | Interop with ranks produced by other libraries without validation |
| Three rotating buckets | Same rebalance trigger thresholds as Jira (128/254) |
| Midpoint insert with scale expansion | Matching Jira’s internal decimal implementation bit-for-bit |

**Recommendation:** treat ranks as opaque strings owned by this library. When importing external rank strings, always `LexoRank::parse()` and persist the **canonical** form. Do not assume another system’s rank string orders identically unless you have verified it with tests.

## Exception taxonomy

All extend `LexoRankException` (implements `LexoRankExceptionInterface`):

| Exception | Typical cause |
|-----------|---------------|
| `InvalidRankFormatException` | Malformed string, whitespace, wrong bucket digit, too long |
| `InvalidBucketException` | Invalid bucket id/string |
| `InvalidBoundsException` | Equal/reversed bounds, different buckets in `between()` |
| `InvalidDigitException` | Internal digit conversion (should not surface in normal use) |
| `DuplicateRankException` | Duplicate in ordered input |
| `InvalidRebalanceInputException` | Unsorted input, invalid count/index |
| `RankSpaceExhaustedException` | `before()` at min, `after()` at max, or rank too long |

## File layout

```
src/
├── LexoRank.php
├── LexoRankBucket.php
├── LexoRankService.php
├── LexoRankCollection.php
├── Rebalancer.php
├── RebalanceResult.php
├── Exception/
├── Math/              # @internal
└── Laravel/
    ├── LexoRankServiceProvider.php
    ├── Casts/
    ├── Concerns/
    ├── Facades/
    └── config/
```

See also: [algorithm.md](algorithm.md), [laravel.md](laravel.md), [rebalancing.md](rebalancing.md), [troubleshooting.md](troubleshooting.md).
