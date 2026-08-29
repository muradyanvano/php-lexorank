# php-lexorank

Production-ready [LexoRank](https://www.atlassian.com/blog/atlassian-engineering/lexorank)-style lexicographic ranking for PHP — deterministic ordered lists, Kanban boards, and drag-and-drop UIs without renumbering entire tables.

[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-%5E8.1-777BB4)](composer.json)

**Package:** `muradyanvano/php-lexorank`  
**Namespace:** `MuradyanVano\LexoRank`  
**Author:** [Vano Muradyan](https://github.com/muradyanvano)

---

## 1. Overview

LexoRank assigns each item a string **rank**. Sorting by that string matches logical order. Inserting between two items computes a new rank from neighbours only — **O(1)** per move, no cascade updates.

This implementation uses **base-36 digit arrays** (no floats, BCMath, or GMP), **three rotating buckets** for safe rebalance migrations, and an optional **Laravel** layer (cast, trait, facade).

---

## 2. Features

- Immutable `LexoRank` value objects with canonical serialization
- `between()`, `before()`, `after()` with scale expansion for adjacent ranks
- Bulk allocation via recursive midpoint partitioning (`betweenMany`, `initialRanks`)
- Rebalancer with explicit old→new mapping (no hidden DB I/O)
- `strcmp` on canonical strings matches `compareTo()`
- Laravel: `LexoRankCast`, `HasLexoRank`, `LexoRankServiceProvider`, `LexoRankFacade`
- PHPStan level 9, strict types, MIT license

---

## 3. Requirements

- PHP **^8.1** (framework-independent core)
- Laravel integration is **optional** (not a runtime dependency)
- Developing/testing the Laravel layer needs **PHP 8.2+** and an explicit install of Laravel 12.61.1+ / Testbench 10 (not part of default `require-dev`, so PHP 8.1 `composer update` keeps working)

---

## 4. Installation

```bash
composer require muradyanvano/php-lexorank
```

Laravel apps auto-discover the service provider. Publish config:

```bash
php artisan vendor:publish --tag=lexorank-config
```

---

## 5. Quick start

```php
use MuradyanVano\LexoRank\LexoRank;
use MuradyanVano\LexoRank\LexoRankService;

$first = LexoRank::middle();           // 0|hzzzzz:
$second = $first->after();             // strictly after first
$between = $first->between($second);   // strictly between

$service = new LexoRankService();
$bulk = $service->initialRanks(10);    // 10 evenly spaced ranks in bucket 0
```

---

## 6. Rank format

Canonical shape: **`{bucket}|{integer}:{fraction}`**

| Part | Rule |
|------|------|
| Bucket | `0`, `1`, or `2` |
| Integer | Base-36 (`0-9a-z`), zero-padded to **6** digits |
| Fraction | Base-36 after `:`, trailing zeros stripped |
| Radix | `:` always present (e.g. `0|hzzzzz:`) |

Sentinels (bucket 0):

| Constant | Value |
|----------|-------|
| Min | `0\|000000:` |
| Max | `0\|zzzzzz:` |
| Middle | `0\|hzzzzz:` |
| Initial | `0\|100000:` |

---

## 7. Buckets

Three buckets rotate `0 → 1 → 2 → 0` for rebalance migrations. Use `inNextBucket()` / `inPreviousBucket()` / `withBucket()` to move the decimal to another bucket without changing its numeric value.

```php
use MuradyanVano\LexoRank\LexoRankBucket;

LexoRank::middle()->inNextBucket()->toString(); // 1|hzzzzz:
LexoRankBucket::bucket0()->next();              // bucket1
```

---

## 8. LexoRank API

| Method | Description |
|--------|-------------|
| `min(?LexoRankBucket $bucket = null)` | Minimum rank |
| `max(?LexoRankBucket $bucket = null)` | Maximum rank |
| `middle(?LexoRankBucket $bucket = null)` | Midpoint between min and max |
| `initial(?LexoRankBucket $bucket = null)` | Starting rank for empty lists |
| `parse(string $rank, int $maxLength = 255)` | Parse or throw |
| `tryParse(string $rank, int $maxLength = 255)` | Parse or `null` |
| `between(LexoRank $other)` | Strictly between (same bucket) |
| `before()` | Previous rank (step 8, or midpoint near bounds) |
| `after()` | Next rank |
| `compareTo(LexoRank $other)` | `<=>` ordering |
| `equals`, `isBefore`, `isAfter` | Comparisons |
| `bucket()` | `LexoRankBucket` |
| `value()`, `toString()`, `__toString()` | Canonical string |
| `inNextBucket()`, `inPreviousBucket()`, `withBucket()` | Bucket moves |
| `isMin()`, `isMax()` | Boundary checks |
| `length()` | Serialized byte length |

```php
$rank = LexoRank::parse('0|100000:');
$rank->between(LexoRank::parse('0|200000:'));
$rank->before();
$rank->after();
```

---

## 9. LexoRankBucket API

| Method | Description |
|--------|-------------|
| `bucket0()`, `bucket1()`, `bucket2()`, `all()` | Factories |
| `fromString(string)`, `fromId(int)` | Parse or throw |
| `tryFromString(string)` | Parse or `null` |
| `next()`, `previous()` | Rotation |
| `equals`, `compareTo`, `value()`, `id()`, `toString()` | Accessors |

---

## 10. LexoRankService API

Constructor: `new LexoRankService(int $maxCount = 100_000, int $maxRankLength = 255)`

| Method | Description |
|--------|-------------|
| `parse`, `tryParse` | Respect service `maxRankLength` |
| `min`, `max`, `middle`, `initial` | Delegates to `LexoRank` |
| `between(?LexoRank $lower, ?LexoRank $upper)` | Open-ended bounds; enforces max length |
| `betweenMany(?$lower, ?$upper, int $count)` | Bulk strictly ordered ranks |
| `initialRanks(int $count, ?LexoRankBucket $bucket = null)` | Evenly spaced in bucket |
| `findDuplicates(iterable $ranks)` | Returns duplicate canonical strings |
| `assertNoDuplicates(iterable $ranks)` | Throws `DuplicateRankException` |
| `maxRankLength()` | Configured limit |

```php
$service = new LexoRankService();
$service->between($lower, $upper);
$service->betweenMany(null, null, 50);
$service->initialRanks(5, LexoRankBucket::bucket1());
```

---

## 11. LexoRankCollection

```php
use MuradyanVano\LexoRank\LexoRankCollection;

$collection = LexoRankCollection::from(['0|100000:', '0|300000:']);
$sorted = $collection->sorted();
$collection->assertStrictlyOrdered();
$collection->duplicates();
$collection->suggestsRebalance(); // default soft length 64
$collection->rankForInsertAt(1); // rank between index 0 and 1
```

Also: `all()`, `count()`, `isEmpty()`, `isStrictlyOrdered()`, `maxLength()`.

---

## 12. Rebalancing

When rank strings grow long from dense inserts, redistribute into the next bucket:

```php
use MuradyanVano\LexoRank\Rebalancer;

$rebalancer = new Rebalancer();
$result = $rebalancer->rebalance($orderedRankStrings);

$result->bucket();
$result->mapping(); // old => new
$result->map('0|very-long-rank:');

$rebalancer->shouldRebalance($ranks, softLength: 64);
```

Apply `mapping()` inside a **database transaction**. See [docs/rebalancing.md](docs/rebalancing.md).

---

## 13. Laravel integration

Optional. The core installs without Laravel. Supported integration testing target:

| Laravel | Testbench | PHP |
|---------|-----------|-----|
| **12.61.1+** | ^10 | **8.2+** |

Default `composer update` in this repo does **not** install Laravel (so PHP 8.1 works). For Laravel tests:

```bash
composer require --dev laravel/framework:^12.61.1 orchestra/testbench:^10.0 larastan/larastan:^3.0
composer test:laravel
```

Laravel 10/11 are **not** claimed or CI-verified (unpatched advisories on those lines).

**Cast:**

```php
protected $casts = ['rank' => \MuradyanVano\LexoRank\Laravel\Casts\LexoRankCast::class];
```

**Trait:**

```php
use MuradyanVano\LexoRank\Laravel\Concerns\HasLexoRank;

$task->moveBetween($before, $after);
$task->moveAfterAndSave($other);
Task::query()->orderByRank()->get();
```

**Facade** (alias `LexoRankFacade`):

```php
use MuradyanVano\LexoRank\Laravel\Facades\LexoRank as LexoRankFacade;

LexoRankFacade::between($lower, $upper);
LexoRankFacade::rebalance($ranks);
```

Full guide: [docs/laravel.md](docs/laravel.md).

---

## 14. Configuration (Laravel)

| Key | Default | Description |
|-----|---------|-------------|
| `max_count` | `100_000` | Bulk allocation cap |
| `max_rank_length` | `255` | Max generated rank length |
| `column` | `rank` | Default column name (trait uses `$lexoRankColumn`) |

---

## 15. Database recommendations

- Column: **`VARCHAR(255)`** (or equivalent), **UNIQUE**
- Store **canonical** strings from `$rank->toString()`
- Scope uniqueness per list: composite `(list_id, rank)` when needed
- Order: `ORDER BY rank ASC` within a single bucket

```php
$table->string('rank', 255)->nullable()->unique();
```

---

## 16. Exceptions

Namespace: `MuradyanVano\LexoRank\Exception\`

| Class | When |
|-------|------|
| `LexoRankException` | Base `InvalidArgumentException` |
| `InvalidRankFormatException` | Bad input string |
| `InvalidBucketException` | Invalid bucket |
| `InvalidBoundsException` | Bad `between()` bounds |
| `DuplicateRankException` | Duplicate rank in set |
| `InvalidRebalanceInputException` | Bad rebalance input |
| `RankSpaceExhaustedException` | Min/max exhausted or rank too long |

---

## 17. Compatibility boundary

Inspired by Atlassian LexoRank and community references (e.g. kvandake). **Not** a byte-for-byte clone.

- Same general format and base-36 lowercase alphabet
- **Not guaranteed:** identical midpoint strings vs Jira or other implementations

Always `parse()` external ranks and persist canonical output. Details: [docs/architecture.md](docs/architecture.md).

---

## 18. Documentation

| Document | Contents |
|----------|----------|
| [docs/architecture.md](docs/architecture.md) | Design, public vs internal API |
| [docs/algorithm.md](docs/algorithm.md) | Grammar, math, invariants |
| [docs/laravel.md](docs/laravel.md) | Eloquent, migrations, transactions |
| [docs/rebalancing.md](docs/rebalancing.md) | Rebalance procedure, concurrency |
| [docs/troubleshooting.md](docs/troubleshooting.md) | Common errors |

---

## 19. Development

```bash
git clone https://github.com/muradyanvano/php-lexorank.git
cd php-lexorank
composer update
composer check    # validate + format-check + phpstan + core tests (PHP 8.1+)
```

Scripts:

| Command | Action |
|---------|--------|
| `composer test:core` | PHPUnit Unit + Integration (PHP 8.1+) |
| `composer test` | All suites (Laravel suite needs extra deps on PHP 8.2+) |
| `composer analyse` | PHPStan level 9 (core; `src/Laravel` excluded) |
| `composer format-check` | PHP CS Fixer dry run |
| `composer validate-package` | Composer schema strict (`composer validate --strict`) |

---

## 20. Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). Please run `composer check` before opening a PR.

---

## 21. Security

See [SECURITY.md](SECURITY.md) for vulnerability reporting and input validation notes.

---

## 22. License

[MIT](LICENSE) © Vano Muradyan

---

## Acknowledgments

LexoRank concept by Atlassian. This library implements an independent PHP design with digit-array arithmetic and documented compatibility limits.
