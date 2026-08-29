# Algorithm

Formal description of rank grammar, arithmetic, and generation rules implemented in this package.

## Rank grammar

```
rank     ::= bucket "|" decimal
bucket   ::= "0" | "1" | "2"
decimal  ::= integer_part ":" fraction_part
integer_part  ::= [0-9a-z]{1,6}   /* zero-padded to width 6 in canonical form */
fraction_part ::= [0-9a-z]*       /* may be empty; trailing "0" digits stripped */
```

### Alphabet

- Base **36**, digits **`0-9a-z`** (lowercase only; uppercase rejected)
- Radix point: **`:`**
- Bucket separator: **`|`** (pipe immediately after the single bucket digit)

### Canonical examples

| Name | Canonical string |
|------|------------------|
| Min (bucket 0) | `0\|000000:` |
| Max (bucket 0) | `0\|zzzzzz:` |
| Middle (bucket 0) | `0\|hzzzzz:` |
| Initial (bucket 0) | `0\|100000:` |
| Initial (bucket 1/2) | `1\|y00000:` / `2\|y00000:` |

The character `y` is digit **34** (`BASE - 2` in base 36).

### Serialization rules

1. Integer part padded with leading `0` to width **6** (`NumeralSystem36::INTEGER_WIDTH`).
2. Radix `:` always present, even when fraction is empty (`0|000000:` not `0|000000`).
3. Trailing fractional zeros stripped (`0|100000:0` → `0|100000:`).
4. `strcmp(canonical_a, canonical_b)` equals `LexoRank::compareTo()` for valid ranks in the same bucket ordering context.

## Buckets

Three buckets rotate for rebalance migrations:

```
0 ──next──▶ 1 ──next──▶ 2 ──next──▶ 0
0 ◀─previous─ 1 ◀─previous─ 2 ◀─previous─ 0
```

- `LexoRankBucket::bucket0()` / `bucket1()` / `bucket2()`
- `next()`, `previous()`, `fromString()`, `fromId()`, `tryFromString()`

Bucket changes **do not** alter the decimal portion:

```php
LexoRank::middle()->inNextBucket()->toString(); // "1|hzzzzz:"
```

## Decimal arithmetic (internal)

Implemented in `Math\LexoDecimal` and `Math\LexoInteger` using **digit arrays**, not floats.

Operations used by rank generation:

- Compare (`compareTo`)
- Add / subtract
- Multiply (including by `0.5` for midpoints)
- Scale expansion / truncation at the radix
- `floor()` / `ceil()` on the integer segment

Constants (`NumeralSystem36`):

| Constant | Value |
|----------|-------|
| `BASE` | 36 |
| `INTEGER_WIDTH` | 6 |
| `STEP` | 8 (used by `before()` / `after()`) |
| `DEFAULT_MAX_RANK_LENGTH` | 255 |

## `between(LexoRank $other)`

**Preconditions:**

- Same bucket (else `InvalidBoundsException::differentBuckets`)
- Not equal (else `InvalidBoundsException::equal`)

**Algorithm (high level):**

1. Order the two decimals as `left < right`.
2. Align scales; if adjacent at coarser scale, expand precision (`betweenDecimals`).
3. Compute midpoint via `(left + right) / 2` in decimal arithmetic, with rounding that keeps `left < mid < right`.
4. Trim unnecessary trailing fractional scale when safe.

When integer parts are adjacent (e.g. `0|i00000:` and `0|i00001:`), the midpoint **must** use a fractional suffix — scale expansion is required.

```php
$left  = LexoRank::parse('0|i00000:');
$right = LexoRank::parse('0|i00001:');
$mid   = $left->between($right);
// $left < $mid < $right
```

## `before()` and `after()`

Step size: **8** in decimal space.

### `before()`

- At **min** → `RankSpaceExhaustedException`
- At **max** → jump to `y00000` (same as initial for buckets 1/2)
- Otherwise: subtract step from floor; if that would cross min, use `between(min, current)`

### `after()`

- At **max** → `RankSpaceExhaustedException`
- At **min** → `0|100000:` (initial for bucket 0)
- Otherwise: add step to ceil; if that would cross max, use `between(current, max)`

These are fast paths for append/prepend-style inserts; dense lists should prefer `between()` on neighbours.

## `LexoRankService::between(?$lower, ?$upper)`

| Lower | Upper | Result |
|-------|-------|--------|
| `null` | `null` | `LexoRank::middle()` |
| `null` | `$upper` | `$upper->before()` |
| `$lower` | `null` | `$lower->after()` |
| both set | ordered, same bucket implied via `$lower->between($upper)` |

Also validates:

- Not equal / not reversed
- Result length ≤ `max_rank_length`

## Bulk allocation: `betweenMany()` / `initialRanks()`

`betweenMany($lower, $upper, $count)`:

1. Resolve effective bounds (open ends use min/max of the resolved bucket).
2. Recursively partition: place midpoint at `count/2`, allocate left and right sub-ranges.
3. Returns **strictly ordered** unique ranks.

`initialRanks($count, $bucket)` = `betweenMany(min($bucket), max($bucket), $count)`.

Count must be `≥ 1` and `≤ max_count` (default 100_000).

## Invariants

The test suite enforces:

1. **Antisymmetry / transitivity** of `compareTo()`
2. **`strcmp` ≡ `compareTo()`** on canonical strings
3. Repeated midpoint insertion stays ordered (200+ iterations)
4. Parsed ranks round-trip to canonical form
5. Strict ordering required for rebalance input

## Ordering in SQL

Because the canonical format uses fixed-width integer segments and ASCII alphabet order matches numeric order in base 36, **`ORDER BY rank ASC`** on the stored string matches PHP ordering **within a single bucket**. During rebalance migrations, filter or scope by bucket until all rows are migrated.

## What not to do

- Do **not** compute midpoints with `(float) $a + (float) $b) / 2` — precision loss breaks ordering.
- Do **not** call `between()` across buckets — use rebalance + bucket rotation instead.
- Do **not** strip the trailing `:` — it is part of the canonical format.
