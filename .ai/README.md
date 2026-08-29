# AI skills — php-lexorank

This directory contains **Cursor Agent Skills** for working on `muradyanvano/php-lexorank`.

## Skill index

| Skill | Use when |
|-------|----------|
| [package-architecture.md](skills/package-architecture.md) | Navigating layers, public vs `@internal` API |
| [php-code-quality.md](skills/php-code-quality.md) | PHPStan, CS Fixer, composer scripts |
| [lexorank-algorithm.md](skills/lexorank-algorithm.md) | Rank generation, buckets, invariants |
| [arbitrary-precision-math.md](skills/arbitrary-precision-math.md) | `Math\*` digit arrays — never use floats |
| [laravel-integration.md](skills/laravel-integration.md) | Service provider, cast, trait, facade |
| [testing.md](skills/testing.md) | PHPUnit suites, Testbench, invariants |
| [documentation.md](skills/documentation.md) | README, docs/, accuracy rules |
| [release-process.md](skills/release-process.md) | Versioning, CHANGELOG, CI gates |

## Skill maintenance rule

**When you change public behavior, APIs, config keys, or documented invariants, update the relevant skill(s) in the same PR.**

Checklist:

1. Identify affected skills from the table above
2. Edit skill markdown to match **actual** `src/` code — never invent methods
3. Update `README.md` and `docs/*.md` if user-facing
4. Add a `CHANGELOG.md` entry under `[Unreleased]` for releases
5. If CI expectations change, update `testing.md` and `.github/workflows/*`

Skills describe **this repository only**. Do not copy patterns from Atlassian Jira or kvandake unless documented as compatibility notes.

## Source of truth

```
src/LexoRank.php
src/LexoRankService.php
src/Rebalancer.php
tests/Unit/
composer.json scripts
```

When skills and docs disagree with code, **code wins** — fix the skill.
