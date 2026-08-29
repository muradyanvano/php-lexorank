# Troubleshooting

Common errors, causes, and fixes when using php-lexorank.

## InvalidRankFormatException

**Symptoms:** `parse()` fails; `tryParse()` returns `null`.

| Cause | Example | Fix |
|-------|---------|-----|
| Empty string | `''` | Provide a valid rank or handle null at DB layer |
| Whitespace | `"0|000000:\n"` | Trim before parse; store canonical only |
| Invalid bucket | `9|000000:` | Bucket must be `0`, `1`, or `2` |
| Uppercase | `0|HZZZZZ:` | Lowercase only; normalize on import |
| Missing radix | `0|000000` | Must include `:` |
| Too long | strlen > `max_rank_length` | Rebalance; widen column only after raising config |

**Tip:** Always persist ` $rank->toString()` after parse to normalize padding and trailing zeros.

## InvalidBoundsException

| Message context | Cause | Fix |
|-----------------|-------|-----|
| Equal bounds | `$a->between($a)` or service with same lower/upper | Use distinct neighbours |
| Reversed bounds | lower after upper in service | Swap or sort first |
| Different buckets | `$a->between($b)` across buckets | Rebalance; use same bucket for inserts |

For open-ended inserts use `LexoRankService::between(null, $upper)` not raw `between()` on cross-bucket values.

## RankSpaceExhaustedException

| Case | API | Fix |
|------|-----|-----|
| Before minimum | `LexoRank::min()->before()` | Insert after min instead, or rebalance |
| After maximum | `LexoRank::max()->after()` | Insert before max, or rebalance |
| Max length | Service generates rank > 255 (default) | **Rebalance** the list |

If you see length errors during normal inserts, ranks are too dense — rebalance earlier (soft threshold 64).

## DuplicateRankException

**Cause:** Two rows or list entries share the same canonical rank.

**Prevention:**

- Unique database index on `rank` (or composite with scope key)
- Use `LexoRankService::assertNoDuplicates()` before bulk writes
- `LexoRankCollection::assertStrictlyOrdered()` for ordered lists

**Recovery:**

1. Find duplicates: `findDuplicates($ranks)` or `LexoRankCollection::duplicates()`
2. Re-assign colliding rows with `between()` on correct neighbours inside a transaction
3. If many collisions, run full **rebalance**

## InvalidRebalanceInputException

| Cause | Fix |
|-------|-----|
| Unsorted input to `rebalance()` | Sort by rank ascending first |
| Invalid count (`< 1` or > `max_count`) | Pass positive count within config |
| Invalid insert index in `rankForInsertAt()` | Index must be `0 … count` inclusive |

## Collisions under concurrency

Two requests inserting between the same neighbours may compute the same rank.

**Symptoms:** Unique constraint violation on save.

**Fix:**

1. Catch duplicate key exception
2. Reload neighbours
3. Retry `between()` (exponential backoff optional)

For high contention, use row locks:

```php
DB::transaction(function () {
    $before = Task::query()->whereKey($id)->lockForUpdate()->first();
    // compute and save
});
```

## Rank string grows without bound

**Cause:** Repeated `between()` on adjacent ranks expands fractional precision.

**Fix:** Monitor `LexoRank::length()` or `$rank->length()`. Call `shouldRebalance()` when max length ≥ 64 (default soft limit).

## SQL order does not match UI order

Checklist:

1. Same **bucket** for all rows in the list?
2. Column collation ASCII/byte order (utf8mb4_bin or C) if locale collation interferes?
3. Using `ORDER BY rank ASC` not numeric cast?
4. Stale ranks after partial rebalance?

## Laravel cast issues

| Issue | Fix |
|-------|-----|
| Rank not a `LexoRank` instance | Add `LexoRankCast` to `$casts` |
| `LexoRankException` on read | DB value malformed; fix data or migrate |
| Facade vs class name clash | Import value object as `LexoRank as Rank` or use `LexoRankFacade` alias |

## Interop with external systems

Ranks from Jira or other libraries may **not** parse or may order differently.

**Fix:** Treat as untrusted input; `parse()` and re-persist canonical form. See compatibility boundary in [architecture.md](architecture.md).

## Debugging checklist

1. `LexoRank::parse($value)->toString()` — canonical form?
2. `$lower->compareTo($upper) < 0` — ordered?
3. `$lower->bucket()->equals($upper->bucket())` — same bucket?
4. `$rank->length()` — approaching 255?
5. Unique index present on rank column?
