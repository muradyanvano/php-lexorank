# Security Policy

## Supported versions

| Version | Supported          |
| ------- | ------------------ |
| 0.1.x   | :white_check_mark: |

## Reporting a vulnerability

**Please do not open public GitHub issues for security vulnerabilities.**

Report privately to **vano.muradyan@example.com** with:

- Description of the issue
- Steps to reproduce
- Impact assessment (if known)
- Suggested fix (optional)

You should receive a response within **7 business days**. We will coordinate disclosure and a fix release if applicable.

## Security considerations for consumers

### Rank string validation

Rank strings from clients or external systems are **untrusted input**.

- Always validate with `LexoRank::parse()` or `tryParse()` before persistence
- Persist only **canonical** output from `$rank->toString()`
- Reject or normalize ranks that exceed `max_rank_length` (default 255)

The parser rejects:

- Empty strings and whitespace
- Invalid bucket digits (only `0`, `1`, `2`)
- Uppercase letters (must be lowercase base-36)
- Missing radix `:`
- Strings longer than the configured max length

### SQL injection

Rank values should be bound as **parameters** in queries, not concatenated into SQL. The library does not execute SQL.

### Denial of service

- `LexoRankService` enforces `max_count` (default 100_000) on bulk allocation
- `max_rank_length` (default 255) limits serialized size
- Very deep recursive `betweenMany()` is bounded by count, not unbounded user strings

For untrusted bulk requests, enforce list-size limits at the application layer and use rate limiting.

### Laravel

- `LexoRankCast` throws on non-string DB values — investigate data corruption if seen in production
- Use unique indexes on rank columns to prevent duplicate-order corruption under concurrency

## Dependencies

Runtime dependency is **PHP only** (`^8.1`). Keep Composer dependencies updated in development and audit with `composer audit` when available.
