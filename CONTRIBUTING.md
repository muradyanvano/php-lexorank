# Contributing

Thank you for contributing to **php-lexorank**.

## Development setup

```bash
git clone https://github.com/muradyanvano/php-lexorank.git
cd php-lexorank
composer install
```

Requirements: PHP **^8.1** for core development (`composer update` / `composer check`). Laravel test suite needs **PHP 8.2+** plus an explicit Laravel 12.61.1+ / Testbench install (see `docs/laravel.md`).

## Quality gate

Before opening a pull request, run:

```bash
composer check
```

This runs, in order:

1. `composer validate --strict`
2. `composer format-check` (PHP CS Fixer)
3. `composer analyse` (PHPStan level 9)
4. `composer test` (PHPUnit)

Individual suites:

```bash
composer test:unit
composer test:integration
composer test:laravel
```

Optional:

```bash
composer infection   # mutation testing, MSI thresholds in infection.json.dist
composer rector-check
```

## Coding standards

- `declare(strict_types=1);` in all PHP files
- PSR-12 via `.php-cs-fixer.dist.php`
- PHPStan level 9 on `src/` (Laravel config excluded)
- `@internal` for `Math\*` — not part of the public semver API

## Pull request expectations

1. **One concern per PR** when possible (feature, fix, or docs — not mixed refactors)
2. **Tests** for behavior changes in `tests/Unit`, `tests/Integration`, or `tests/Laravel`
3. **No invented public API** — document only what exists in `src/`
4. **Docs** updated when behavior or public surface changes
5. **CHANGELOG.md** entry under `[Unreleased]` for user-visible changes

## Documentation and AI skills

When changing public behavior, update:

- `README.md` and relevant `docs/*.md`
- `.ai/skills/*.md` if agent guidance is affected

See `.ai/README.md` for skill maintenance rules.

## Reporting issues

Use GitHub Issues with the bug or feature template. For security issues, see [SECURITY.md](SECURITY.md).

## License

By contributing, you agree that your contributions will be licensed under the [MIT License](LICENSE).
