# Skill: Laravel integration

**Repo:** `muradyanvano/php-lexorank`  
**Path:** `src/Laravel/`

## Auto-discovery

`composer.json` extra.laravel registers:

- `LexoRankServiceProvider`
- Facade alias **`LexoRankFacade`** (not `LexoRank` — collides with value object)

## Optional dependency rule

- Core `require` is only `php: ^8.1`.
- `laravel/framework` (`^12.61.1`), `orchestra/testbench` (`^10.0`), and `larastan/larastan` (`^3.0`) are **require-dev**.
- Consumers install the package without Laravel.
- Laravel integration tests require **PHP 8.2+**.

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

## Testbench / CI matrix

| PHP | What runs | Laravel | Testbench |
|-----|-----------|---------|-----------|
| 8.1 | Unit + Integration only | — (deps stripped in CI) | — |
| 8.2–8.4 | Unit + Integration + Laravel + audit | **12.61.1+** | **^10** |

Do **not** claim Laravel 10/11 support: those lines remain affected by published advisories and are not CI-verified.

```bash
composer test:laravel   # requires PHP 8.2+ and Laravel 12.61.1+
```

Uses Orchestra Testbench, SQLite in-memory, sample `Task` model with cast + trait.

## Migrations guidance

`string('rank', 255)->nullable()->unique()` — document in `docs/laravel.md`

## Do not

- Add facades for `LexoRank` value object
- Persist inside trait methods except `*AndSave` variants
- Pull Laravel into core `src/LexoRank.php` etc.
- Ignore Composer security advisories to keep old Laravel lines

## When editing Laravel layer

Update: `docs/laravel.md`, README Laravel sections, `tests/Laravel/`, this skill, `AGENTS.md` if compatibility matrix changes.
