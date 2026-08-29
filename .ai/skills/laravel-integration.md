# Skill: Laravel integration

**Repo:** `muradyanvano/php-lexorank`  
**Path:** `src/Laravel/`

## Auto-discovery

`composer.json` extra.laravel registers:

- `LexoRankServiceProvider`
- Facade alias **`LexoRankFacade`** (not `LexoRank` — collides with value object)

## Optional dependency rule

- Core `require` is only `php: ^8.1`.
- Default `require-dev` does **not** include Laravel, Testbench, or Larastan — so `composer update` works on PHP 8.1.
- CI Laravel jobs install: `laravel/framework:^12.61.1`, `orchestra/testbench:^10.0`, `larastan/larastan:^3.0`.
- Consumers install the package without Laravel.
- Laravel integration tests require **PHP 8.2+**.

## ServiceProvider / Cast / Trait / Facade

Unchanged API — see `docs/laravel.md` and README.

## Testbench / CI matrix

| PHP | What runs | How deps are installed |
|-----|-----------|------------------------|
| 8.1–8.4 | Unit + Integration + audit | Default `composer update` |
| 8.2–8.4 | Laravel suite + audit | `composer require --dev` Laravel 12.61.1+ / Testbench 10 |

```bash
composer test:laravel
vendor/bin/phpstan analyse -c phpstan-laravel.neon.dist --memory-limit=512M
```

## Do not

- Put Laravel 10/11 back into default `require-dev` (breaks audit + PHP 8.1 installs)
- Pull Laravel into core domain classes
- Ignore Composer security advisories

## When editing Laravel layer

Update: `docs/laravel.md`, README, `tests/Laravel/`, this skill, `AGENTS.md`.
