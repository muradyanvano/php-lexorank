# Laravel integration

Optional Eloquent helpers for applications using Laravel. The core package has **no** Laravel runtime dependency; Laravel code lives under `MuradyanVano\LexoRank\Laravel\`.

## Installation

```bash
composer require muradyanvano/php-lexorank
```

Laravel **auto-discovers** `LexoRankServiceProvider` via `composer.json` `extra.laravel`.

Publish config (optional):

```bash
php artisan vendor:publish --tag=lexorank-config
```

## Configuration

`config/lexorank.php`:

| Key | Default | Purpose |
|-----|---------|---------|
| `max_count` | `100_000` | Cap for `betweenMany()` / `initialRanks()` |
| `max_rank_length` | `255` | Max canonical string length before `RankSpaceExhaustedException` |
| `column` | `rank` | Documentation default; trait uses `$lexoRankColumn` on the model |

## Service container

Registered singletons:

- `MuradyanVano\LexoRank\LexoRankService`
- `MuradyanVano\LexoRank\Rebalancer`

Resolve directly or via the facade:

```php
use MuradyanVano\LexoRank\LexoRankService;
use MuradyanVano\LexoRank\Laravel\Facades\LexoRank as LexoRankFacade;

$service = app(LexoRankService::class);
$rank = LexoRankFacade::between($lower, $upper);
```

**Facade alias:** `LexoRankFacade` (avoids collision with the `LexoRank` value object class).

`LexoRankFacade::rebalance()` delegates to the `Rebalancer` binding.

## Eloquent cast

```php
use Illuminate\Database\Eloquent\Model;
use MuradyanVano\LexoRank\Laravel\Casts\LexoRankCast;
use MuradyanVano\LexoRank\LexoRank;

class Task extends Model
{
    protected $casts = [
        'rank' => LexoRankCast::class,
    ];
}

$task->rank = LexoRank::middle();
$task->save();

$task->fresh()->rank instanceof LexoRank; // true
```

- **Get:** `null` or string → `LexoRank` (via `LexoRank::parse()`)
- **Set:** `LexoRank`, canonical string, or `null` → stored string

Invalid types throw `InvalidRankFormatException` or `LexoRankException`.

## HasLexoRank trait

```php
use MuradyanVano\LexoRank\Laravel\Concerns\HasLexoRank;

class Task extends Model
{
    use HasLexoRank;

    // optional: protected string $lexoRankColumn = 'sort_rank';
}
```

| Method | Persists? | Description |
|--------|-----------|-------------|
| `getLexoRankColumn()` | — | Column name (default `rank`) |
| `getLexoRank()` | — | Current rank as `LexoRank` or `null` |
| `setLexoRank(LexoRank\|string\|null)` | No | Set attribute only |
| `moveBefore($other)` | No | Rank strictly before `$other` |
| `moveAfter($other)` | No | Rank strictly after `$other` |
| `moveBetween(?$before, ?$after, ?LexoRankService)` | No | Between neighbours; open ends allowed |
| `moveBeforeAndSave($other)` | Yes | `moveBefore` + `save()` |
| `moveAfterAndSave($other)` | Yes | `moveAfter` + `save()` |
| `moveBetweenAndSave(?$before, ?$after, ?LexoRankService)` | Yes | `moveBetween` + `save()` |
| `scopeOrderByRank($query, 'asc'\|'desc')` | — | `orderBy` rank column |

If a neighbour has `null` rank, `moveBefore` / `moveAfter` fall back to `LexoRank::middle()`.

## Migrations

### MySQL

```php
Schema::create('tasks', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->string('rank', 255)->nullable()->unique();
    $table->timestamps();
});
```

### PostgreSQL

```php
$table->string('rank', 255)->nullable()->unique();
// or: $table->char('rank', 255)->nullable()->unique();
```

### SQLite

```php
$table->string('rank')->nullable()->unique();
```

**Recommendations:**

- Length **255**, **unique** index on `rank`
- Nullable if items can exist before first ordering
- For multi-tenant lists, use a **composite unique** `(tenant_id, rank)` instead of global unique

## Transactions

Always wrap rank updates that must stay consistent:

```php
use Illuminate\Support\Facades\DB;

DB::transaction(function () use ($task, $before, $after) {
    $task->moveBetween($before, $after);
    $task->save();
});
```

For drag-and-drop between two neighbours, load both in the same transaction and use `moveBetween($before, $after)` to avoid duplicate ranks under concurrency (unique index will still catch races — retry or lock rows).

## Rebalancing in Laravel

See [rebalancing.md](rebalancing.md). Summary:

```php
use MuradyanVano\LexoRank\Laravel\Facades\LexoRank as LexoRankFacade;

$tasks = Task::query()->orderByRank()->get();
$result = LexoRankFacade::rebalance($tasks->pluck('rank'));

DB::transaction(function () use ($tasks, $result) {
    foreach ($tasks as $task) {
        $new = $result->map($task->rank->toString());
        $task->update(['rank' => $new]);
    }
});
```

Handle unique-index conflicts during two-phase bucket migration as described in rebalancing docs.

## Ordering queries

```php
Task::query()->orderByRank()->get();
Task::query()->orderByRank('desc')->get();
```

Equivalent to `orderBy('rank', $direction)` using the model’s rank column.
