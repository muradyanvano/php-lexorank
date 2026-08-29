# Skill: Laravel integration

**Repo:** `muradyanvano/php-lexorank`  
**Path:** `src/Laravel/`

## Auto-discovery

`composer.json` extra.laravel registers:

- `LexoRankServiceProvider`
- Facade alias **`LexoRankFacade`** (not `LexoRank` — collides with value object)

## ServiceProvider

File: `LexoRankServiceProvider.php`

Registers singletons:

- `LexoRankService` — reads `config('lexorank.max_count')`, `max_rank_length`
- `Rebalancer` — injected with service

Publishes config tag: `lexorank-config`

## Config keys (`config/lexorank.php`)

| Key | Default |
|-----|---------|
| max_count | 100_000 |
| max_rank_length | 255 |
| column | rank |

Trait uses model property `$lexoRankColumn`, not config `column`, for column name.

## LexoRankCast

- get: string|null → LexoRank|null via `LexoRank::parse()`
- set: LexoRank|string|null → canonical string|null
- Wrong DB types → `LexoRankException`

## HasLexoRank trait

| Method | Saves? |
|--------|--------|
| moveBefore, moveAfter, moveBetween | No |
| moveBeforeAndSave, moveAfterAndSave, moveBetweenAndSave | Yes |
| scopeOrderByRank | query scope |

Null neighbour rank → falls back to `LexoRank::middle()`.

## Facade

Class: `MuradyanVano\LexoRank\Laravel\Facades\LexoRank`

- Default accessor: `LexoRankService::class`
- Static `rebalance()` → resolves `Rebalancer` from container

Import pattern:

```php
use MuradyanVano\LexoRank\LexoRank as Rank;
use MuradyanVano\LexoRank\Laravel\Facades\LexoRank as LexoRankFacade;
```

## Testbench

Tests: `tests/Laravel/LaravelIntegrationTest.php`

```bash
composer test:laravel
```

Uses Orchestra Testbench, SQLite in-memory, sample `Task` model with cast + trait.

## Migrations guidance

`string('rank', 255)->nullable()->unique()` — document in `docs/laravel.md`

## Do not

- Add facades for `LexoRank` value object
- Persist inside trait methods except `*AndSave` variants
- Pull Laravel into core `src/LexoRank.php` etc.

## When editing Laravel layer

Update: `docs/laravel.md`, README §13–14, `tests/Laravel/`, this skill.
