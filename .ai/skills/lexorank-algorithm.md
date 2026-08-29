# Skill: LexoRank algorithm

**Repo:** `muradyanvano/php-lexorank`  
**Critical:** Never replace midpoint logic with floating-point arithmetic.

## Canonical format

```
{bucket}|{6-digit-base36-int}:{optional-fraction}
```

- Bucket: `0`, `1`, `2`
- Alphabet: `0-9a-z` lowercase
- Min / max / middle (bucket 0): `0|000000:` / `0|zzzzzz:` / `0|hzzzzz:`
- Initial bucket 0: `0|100000:`; buckets 1/2: `{n}|y00000:` (`y` = digit 34)

## Ordering invariant

```php
strcmp($a->toString(), $b->toString()) === $a->compareTo($b);
```

Enforced in `tests/Unit/LexoRankTest.php`.

## between(LexoRank $other)

- **Same bucket required** → else `InvalidBoundsException::differentBuckets`
- **Not equal** → else `InvalidBoundsException::equal`
- Order-agnostic: internally sorts decimals
- **Adjacent integers** require fractional scale expansion (`betweenDecimals`)

Implementation: `LexoRank::betweenDecimals()` in `src/LexoRank.php` — align scales, shrink scale when safe, midpoint via decimal `add` + `multiply(half)`.

## before() / after()

- Step = **8** (`NumeralSystem36::STEP`)
- `before()` at min → `RankSpaceExhaustedException`
- `after()` at max → `RankSpaceExhaustedException`
- At max, `before()` → `y00000`; at min, `after()` → `100000`

## LexoRankService::between(?$lower, ?$upper)

| Args | Behavior |
|------|----------|
| null, null | `middle()` |
| null, upper | `upper->before()` |
| lower, null | `lower->after()` |
| both | ordered check + `lower->between(upper)` + length assert |

## Bulk: betweenMany / initialRanks

Recursive midpoint partition in `LexoRankService::allocateBetween()` — not repeated single inserts.

## Buckets

Rotation for **rebalance migration only**. `inNextBucket()` keeps decimal, changes bucket prefix.

Insert math (`between`) does **not** cross buckets.

## Rebalancer

Input: strictly ordered, unique ranks.  
Output: `initialRanks(count, nextBucket)` + mapping old→new.

## Anti-patterns (reject in review)

```php
// WRONG — float midpoint
$mid = ($a + $b) / 2;

// WRONG — BCMath/GMP for rank generation (not used in this repo)

// WRONG — between across buckets
$rank0->between($rank1); // different buckets

// WRONG — strip trailing ':'
'0|hzzzzz' // invalid canonical
```

## Constants (NumeralSystem36)

| Name | Value |
|------|-------|
| INTEGER_WIDTH | 6 |
| STEP | 8 |
| DEFAULT_MAX_RANK_LENGTH | 255 |
| BASE | 36 |

## Tests to run after algorithm changes

```bash
composer test:unit
# especially LexoRankTest, InvariantTest, MathTest, ServiceAndRebalancerTest
```
