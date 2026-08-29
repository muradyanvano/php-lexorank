# Skill: Arbitrary-precision math

**Repo:** `muradyanvano/php-lexorank`  
**Namespace:** `MuradyanVano\LexoRank\Math` — **@internal**

## Why digit arrays

LexoRank midpoints require exact base-36 arithmetic. PHP floats lose precision; this package uses **digit arrays** instead of float, **BCMath**, or **GMP**.

## Classes

| Class | Role |
|-------|------|
| `NumeralSystem36` | Alphabet, `toChar`/`toDigit`, constants (BASE=36, INTEGER_WIDTH=6, STEP=8, RADIX=':') |
| `LexoInteger` | Whole numbers in base 36 |
| `LexoDecimal` | Integer + fractional scale at `:` |

## LexoDecimal operations (used by LexoRank)

- `parse(string)`, `format()`
- `compareTo`, `equals`
- `add`, `subtract`, `multiply` (including half)
- `setScale`, `scale()`
- `floor`, `ceil`, `fromInteger`

## Integration points

- `LexoRank::formatDecimal()` — pad integer to 6, strip trailing fractional zeros, keep `:`
- `LexoRank::betweenDecimals()` — midpoint pipeline
- `before()` / `after()` — floor/ceil + step add/subtract

## Rules for contributors

1. **Never** introduce `float` casts for rank values
2. **Never** add BCMath/GMP as runtime dependencies for rank math
3. Keep Math `@internal` — applications use `LexoRank` strings
4. New operations belong in `LexoDecimal`/`LexoInteger`, not scattered in `LexoRank`
5. Throw `InvalidDigitException` for invalid characters at math layer

## Midpoint implementation sketch

Correct approach in this repo:

```php
$sum = $left->add($right);
$mid = $sum->multiply(LexoDecimal::half());
// + scale alignment and checkMid bounds in LexoRank::betweenDecimals
```

Wrong approach:

```php
// DO NOT ADD
$mid = LexoDecimal::parse((string) ((float) $left + (float) $right) / 2);
```

## Testing

`tests/Unit/MathTest.php` — run after any Math change:

```bash
composer test:unit -- --filter MathTest
```

## Serialization coupling

Math output feeds `formatDecimal()`. Changes to `format()` or padding rules affect:

- Parse round-trip tests
- `strcmp` ordering tests
- Max length / rebalance behavior

Update `tests/Unit/LexoRankTest.php` and `InvariantTest.php` when format rules change.
