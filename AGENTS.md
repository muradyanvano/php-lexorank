# AGENTS.md

Instructions for coding agents working on `muradyanvano/php-lexorank`.

## Always read first

1. Read `.ai/README.md`.
2. Read every applicable file under `.ai/skills/` before changing code, CI, docs, or dependencies.
3. Treat `src/` as authoritative when skills are stale — then fix the skill in the same change.

## Project identity

- Composer package: `muradyanvano/php-lexorank`
- Namespace: `MuradyanVano\LexoRank`
- Runtime: PHP `^8.1`, **no** Laravel runtime dependency
- Laravel / Testbench / Larastan are **require-dev only** (optional integration)

## Compatibility (must stay truthful)

| Surface | Support |
|---------|---------|
| Core library | PHP 8.1+ |
| Laravel integration tests | Laravel **12.61.1+** via Orchestra Testbench **^10**, PHP **8.2+** |
| Do not claim | Laravel 10/11 as supported (unpatched / not CI-verified) |

PHP 8.1 CI runs **Unit + Integration only** (Laravel deps stripped). PHP 8.2+ runs Laravel suite + `composer audit`.

## Security / Composer rules

- Never suppress or ignore security advisories.
- Never set `audit.block-insecure` to `false`.
- Never use `--ignore-platform-reqs` to paper over version conflicts.
- Dev lockset must keep `laravel/framework` at **≥ 12.61.1** (or ≥ 13.12.0 if/when adopted).
- Plain PHP consumers must install without requiring Laravel.

## Quality gates (run before finishing)

```bash
composer validate --strict
composer install   # or update when lock is absent / intentionally refreshed
composer test
composer analyse
composer format-check
composer audit
composer check     # validate-package + format-check + analyse + test
```

Do not claim a command passed unless it was executed successfully in this environment.

## Algorithm / architecture constraints

- No PHP `float` rank math; no mandatory BCMath/GMP for core.
- Exact digit-array arithmetic under `src/Math/` (`@internal`).
- Prefer no runtime dependencies for the core.
- Do not replace LexoRank with naive fractional float indexing.
- Public API vs `@internal` boundaries: see `.ai/skills/package-architecture.md` and `.ai/skills/lexorank-algorithm.md`.

## Change synchronization

Any change that affects behavior, public API, supported versions, CI, commands, or release workflow must update the matching `.ai` skill(s), `README.md` / `docs/*`, and `CHANGELOG.md` in the same change.

## Do not

- Publish to Packagist, create Git tags, or push unless the human explicitly asks.
- Remove Laravel integration tests.
- Add Laravel types/imports to core domain classes.
- Invent undocumented Atlassian-compatible behavior.

## Task template: dependency / audit fixes

When fixing `composer audit` findings for Laravel:

1. Inspect `composer.json`, lockfile, workflows, Laravel tests, docs.
2. Run `composer show` / `why` / `outdated` / `why-not` for the affected packages.
3. Move to a patched Laravel line (12.61.1+ or 13.12.0+).
4. Keep core PHP 8.1; adjust CI matrix so unsupported PHP/Laravel pairs are not claimed or tested.
5. Re-run full quality gates including `composer audit`.
6. Report root cause, version delta, files changed, and every command result.
