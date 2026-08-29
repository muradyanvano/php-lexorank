# Rebalancing

Rebalancing redistributes ranks evenly in a **new bucket** when strings grow too long from repeated midpoint inserts.

## When to rebalance

| Signal | API |
|--------|-----|
| Any rank length ≥ soft threshold (default **64**) | `Rebalancer::shouldRebalance($ranks)` |
| Collection helper | `LexoRankCollection::suggestsRebalance($softLength)` |
| Hard limit approaching | `max_rank_length` (default **255**) — `RankSpaceExhaustedException` on generate |

Jira-inspired guidance: plan rebalance before ranks approach ~128 characters; hard stop at ~254. This library defaults soft check at **64** to leave headroom.

## Procedure

The library **does not mutate persistence**. It returns a `RebalanceResult`:

```php
use MuradyanVano\LexoRank\Rebalancer;
use MuradyanVano\LexoRank\LexoRankService;

$rebalancer = new Rebalancer(new LexoRankService());
$result = $rebalancer->rebalance($orderedRankStrings);

$result->bucket();    // LexoRankBucket — target bucket
$result->ranks();      // list<LexoRank> — new evenly spaced ranks
$result->mapping();    // array oldCanonical => newCanonical
$result->count();      // int
$result->map($old);    // ?string new rank for one old value
```

### Input requirements

1. Ranks in **strict ascending** order (by `compareTo`)
2. **No duplicates**
3. Iterable of `string` or `LexoRank`

Violations throw `InvalidRebalanceInputException` or `DuplicateRankException`.

### Target bucket

- Default: **next bucket** after the first rank’s bucket (`$parsed[0]->bucket()->next()`)
- Empty input: bucket **1** (or explicit `$targetBucket`)
- Override: `rebalance($ranks, LexoRankBucket::bucket2())`

New ranks are from `initialRanks(count, $targetBucket)` — evenly spaced between min and max in that bucket.

### Application steps

1. **Load** ordered rows (e.g. `ORDER BY rank ASC` scoped to one list/column).
2. **Call** `rebalance()`.
3. **Inside a transaction**, apply `mapping()` to each row.
4. **Verify** no duplicate ranks remain (`assertNoDuplicates` or unique index).
5. **Commit**.

```php
DB::transaction(function () use ($items, $result) {
    foreach ($items as $item) {
        $old = $item->rank->toString();
        $new = $result->map($old);
        if ($new === null) {
            throw new RuntimeException("Missing mapping for {$old}");
        }
        $item->rank = $new;
        $item->save();
    }
});
```

## Bucket rotation strategy

During migration from bucket `0` to bucket `1`:

- Old rows still have `0|…` ranks until updated
- New ranks are `1|…`
- **Do not** mix buckets in `between()` — inserts should use ranks in the **active** bucket only
- After full migration, all rows share the new bucket and ordering by string works again

Rotation cycle: `0 → 1 → 2 → 0`.

## Concurrency risks

| Risk | Mitigation |
|------|------------|
| Two clients insert between same neighbours | **Unique index** on rank; retry with fresh neighbours |
| Rebalance while inserts continue | **Lock list** or maintenance mode; or rebalance a snapshot and reject stale writes |
| Partial rebalance commit | Single **transaction**; rollback on any failure |
| Unique violation when swapping ranks | Two-phase update (see below) |

### Unique index during bulk update

Updating rank A→A′ and B→B′ can temporarily collide if A′ equals an existing B. Strategies:

1. **Nullable staging column** — write new ranks to `rank_staging`, then swap in one statement
2. **Two-pass update** — first set ranks to temporary unique placeholders (UUID prefix), then apply final mapping
3. **Defer constraint** — only if your DB supports deferrable unique constraints (PostgreSQL)

Example two-pass with temporary prefix (conceptual):

```php
DB::transaction(function () use ($items, $result) {
    foreach ($items as $i => $item) {
        $item->update(['rank' => 'tmp|' . $i . '|' . Str::uuid()]);
    }
    foreach ($items as $item) {
        $item->update(['rank' => $result->map($item->getOriginal('rank'))]);
    }
});
```

Ensure temporary values fit column length and are not valid LexoRank strings you might parse elsewhere.

## Laravel facade

```php
use MuradyanVano\LexoRank\Laravel\Facades\LexoRank as LexoRankFacade;

$result = LexoRankFacade::rebalance(
    Task::query()->orderByRank()->pluck('rank')
);
```

## After rebalance

- Rank strings are **shorter** and evenly distributed
- Future inserts use the new bucket consistently
- Monitor length again with `shouldRebalance()` / `suggestsRebalance()`

See [troubleshooting.md](troubleshooting.md) for duplicate and length errors.
