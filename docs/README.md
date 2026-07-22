# PHP Snowflake

Snowflake IDs in PHP, with optional first-class Laravel integration.

[Snowflake IDs](https://en.wikipedia.org/wiki/Snowflake_ID) provide distributed, time-based unique identifiers that avoid the pitfalls of traditional auto-incrementing IDs, and offer a cleaner alternative to large ULIDs/UUIDs. They enable efficient sorting, reduce database contention, and work well in distributed environments.

PHP-Snowflake prioritises timestamp accuracy over extensive sequencing. By using a microsecond-precise timestamp, it significantly reduces the chances of generating sequentially enumerable IDs — a common issue with standard Snowflake and ULID implementations when producing multiple IDs per second or millisecond.

The ID structure:

- **Timestamp (50 bits)** — precise ordering and uniqueness
- **Cluster (5 bits) & Worker (5 bits)** — distributed ID generation
- **Sequence (4 bits)** — up to 16 unique IDs per microsecond

## Documentation

- [Installation](installation/README.md)
- [Usage](usage/README.md)
- [Concurrency](concurrency/README.md)
- [Testing](testing/README.md)
- [Laravel integration](laravel/README.md)

## Requirements

- PHP 8.4+
- Laravel 13+ (optional, for the Eloquent / config integration)

## Quick start

```bash
composer require bradietilley/php-snowflake
```

```php
use BradieTilley\Snowflake\Snowflake;

Snowflake::configure('2025-01-01 00:00:00', cluster: 1, worker: 1);

echo Snowflake::id(); // 9048372019229466888
```

See [Installation](installation/README.md) and [Usage](usage/README.md) for the full walkthrough.

## Comparison

| Feature | Auto-Inc IDs | UUIDs | ULIDs | Traditional Snowflake | This package |
| --- | --- | --- | --- | --- | --- |
| Easy to read/copy/relay | ✅ Yes | ❌ No | ❌ No | ✅ Yes | ✅ Yes |
| Stored as BIGINT | ✅ Yes | ❌ No | ❌ No | ✅ Yes | ✅ Yes |
| Hides table count | ❌ No | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes |
| Globally scalable | ❌ No | ✅ Yes | ✅ Yes | ✅ Yes | ✅ Yes |
| Protects enumeration… | ❌ No | ✅ Yes (ms) | ✅ Yes (ms) | ✅ Yes (ms) | ✅ Yes (μs) |
| Sortable by time | ✅ Yes | ✅ Yes (if time-sorted) | ✅ Yes | ✅ Yes | ✅ Yes |
| Suitable for Primary/Foreign Key | ✅ Yes | ❌ No | ❌ No | ✅ Yes | ✅ Yes |
| Can be sent as number in JSON | ✅ Yes (until 2^53) | ❌ String | ❌ String | ❌ String | ❌ String |
