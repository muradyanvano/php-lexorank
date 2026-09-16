# Skill: Documentation

**Repo:** `muradyanvano/php-lexorank`

## Doc locations

| Path | Audience |
|------|----------|
| `README.md` | First contact — 22 numbered sections + Demo application |
| `docs/architecture.md` | Design, public vs internal, compatibility |
| `docs/algorithm.md` | Grammar, buckets, between/before/after |
| `docs/laravel.md` | Eloquent, migrations, transactions |
| `docs/rebalancing.md` | Procedure, concurrency, unique index |
| `docs/troubleshooting.md` | Errors and fixes |
| `CHANGELOG.md` | Releases |
| `CONTRIBUTING.md`, `SECURITY.md`, `CODE_OF_CONDUCT.md` | Community |
| [php-lexorank-demo-app](https://github.com/muradyanvano/php-lexorank-demo-app) | Official demo (separate repo) — Kanban + REST example |

## Accuracy rules

1. **Only document methods that exist** in `src/` — grep before writing
2. Use **real examples** from tests (`0|hzzzzz:`, `0|100000:`, etc.)
3. Namespace: `MuradyanVano\LexoRank`
4. Facade alias is **`LexoRankFacade`**, not `LexoRank`
5. Mark `@internal` Math as not public API; do not document `LexoRank::from()`, `decimal()`, or `betweenDecimals()` as consumer APIs
6. State compatibility boundary: inspired by Atlassian, not byte-for-byte clone
7. Keep Composer package `muradyanvano/php-lexorank` and GitHub repo `muradyanvano/php-lexorank` aligned; do not rename the PHP namespace when updating identity metadata

## README structure (22 numbered sections + demo)

1. Overview  
2. Features  
3. Requirements  
4. Installation  
5. Quick start  
- **Demo application** (unnumbered; keep after Quick start, before Rank format)
6. Rank format  
7. Buckets  
8. LexoRank API  
9. LexoRankBucket API  
10. LexoRankService API  
11. LexoRankCollection  
12. Rebalancing  
13. Laravel integration  
14. Configuration  
15. Database recommendations  
16. Exceptions  
17. Compatibility boundary  
18. Documentation index  
19. Development  
20. Contributing  
21. Security  
22. License  

Keep numbered section numbers stable when editing. Preserve the **Demo application** section and its link to `https://github.com/muradyanvano/php-lexorank-demo-app`.

### Demo application rules

- Official documentation resource; maintained in a **separate** repository.
- Demo app dependencies (Laravel 13, React, etc.) are **not** dependencies of this PHP package.
- The demo’s Laravel version must **not** redefine this package’s CI / compatibility matrix (official Laravel target remains as documented in README §3 / §13 and `docs/laravel.md`).

## Code examples

- Prefer `LexoRankService` for nullable `between()`
- Use `LexoRank::between()` only with two same-bucket ranks
- Show transaction wrapper for Laravel saves
- Never show float midpoint or non-existent helpers

## Cross-links

Link between README ↔ docs/*.md. Avoid duplicating full algorithm spec in README — link to `docs/algorithm.md`.

## When to update

| Change | Update |
|--------|--------|
| New public method | README §8–11, architecture.md, relevant skill |
| Config key | laravel.md, README §14, laravel-integration skill |
| Exception | README §16, troubleshooting.md |
| CI script | CONTRIBUTING.md, testing skill |

## Style

- Professional, concise, complete sentences
- Tables for API listings
- MIT license, author Vano Muradyan
