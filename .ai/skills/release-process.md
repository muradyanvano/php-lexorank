# Skill: Release process

**Repo:** `muradyanvano/php-lexorank`  
**Suggested first tag:** `v0.1.0`

## Pre-release checklist

1. `composer check` passes on PHP 8.1–8.4 (CI green)
2. `CHANGELOG.md` — move `[Unreleased]` items to version section with date
3. README version badges accurate if used
4. `.ai/skills/*` and `docs/*` match `src/`
5. No `@internal` types leaked into documented public API
6. `composer.json` version not required (tag-driven for libraries)

## Versioning

[Semantic Versioning](https://semver.org/):

- **MAJOR** — breaking public API
- **MINOR** — backward-compatible features
- **PATCH** — backward-compatible fixes

Public API = everything not marked `@internal` in `src/` except `Math\*`.

## CHANGELOG format

Keep a Changelog 1.1.0:

```markdown
## [Unreleased]

## [0.1.0] - YYYY-MM-DD
### Added
- ...
```

Update compare links at bottom of CHANGELOG.

## Git tag

```bash
git tag -a v0.1.0 -m "Release v0.1.0"
git push origin v0.1.0
```

(Do not push unless maintainer requests.)

## Packagist

Package name: `muradyanvano/php-lexorank`  
GitHub repository: `https://github.com/muradyanvano/php-lexorank`

Ensure `composer.json` has valid `name`, `license`, `autoload`, and `support` / `homepage` URLs pointing at **`muradyanvano/php-lexorank`** on GitHub.

## CI gates (must pass)

From `.github/workflows/`:

- **tests.yml** — PHPUnit core matrix (8.1–8.4), Laravel 12 on PHP 8.2+, composer validate, `composer audit` on Laravel jobs
- **static-analysis.yml** — PHPStan, format-check, `composer audit`

Local equivalent (PHP 8.2+ recommended for Laravel + audit):

```bash
composer check
composer audit
```

Dev dependency floor: `laravel/framework ^12.61.1` (patched). Do not reintroduce Laravel 10/11 into the verified matrix.

## Post-release

1. Open new empty `[Unreleased]` section in CHANGELOG
2. Bump any version docs if maintained
3. Create GitHub Release notes from CHANGELOG section

## Security releases

Follow [SECURITY.md](../SECURITY.md) — private report, patch release, advisory if needed.

## Do not

- Amend tagged releases without maintainer approval
- Document unreleased APIs in README
- Skip CHANGELOG for user-visible changes
