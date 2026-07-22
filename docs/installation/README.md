# Installation

```bash
composer require bradietilley/php-snowflake
```

No framework dependency is required for the core generator. Laravel integration is optional and auto-discovered when running inside a Laravel application — see [Laravel integration](../laravel/README.md).

## Requirements

| Package | PHP | Laravel (optional) |
| --- | --- | --- |
| v2.x | 8.4+ | 13+ |

Laravel 11 and 12 are not supported on the current Laravel integration line. The previous standalone `bradietilley/laravel-snowflake` v1.x package remains available for those versions. See the [changelog](../../CHANGELOG.md) for breaking changes.

## Next steps

- [Usage](../usage/README.md) — generating and configuring IDs
- [Concurrency](../concurrency/README.md) — sequence resolvers and locking
- [Laravel integration](../laravel/README.md) — Eloquent trait, config, and testing helpers
