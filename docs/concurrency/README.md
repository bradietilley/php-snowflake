# Concurrency

Within a single process, it is nearly impossible for two Snowflake IDs to share the same timestamp. In high-concurrency environments, however, multiple IDs can land in the same microsecond. Sequencing resolves those collisions.

Avoiding sequencing is preferred when possible (it reduces predictability), but it becomes necessary when generating multiple IDs within the same microsecond.

To ensure uniqueness across concurrent requests, use a sequence resolver that coordinates across processes. File-based locking works out of the box; under Laravel, the cache-backed resolver is preferred for multi-server deployments.

## File resolver

By default, PHP Snowflake uses a `FileSequenceResolver` with locking. It points at a file within this package (for guaranteed readability from the PHP process). You can configure the path or swap the resolver entirely — explicitly configuring this is recommended.

```php
use BradieTilley\Snowflake\Snowflake;
use BradieTilley\Snowflake\SequenceResolvers\FileSequenceResolver;

$file = __DIR__.'/snowflake-concurrency.json';
Snowflake::sequenceResolver(new FileSequenceResolver($file));

Snowflake::id(); // guaranteed unique, even in the same microsecond as another process
```

## Laravel

If you are using Laravel, this package ships a cache-based `LaravelSequenceResolver` that is wired up automatically. See [Laravel integration](../laravel/README.md#concurrency).

It does **not** take a cache lock. For each microsecond timestamp it:

1. Attempts `Cache::add($key, …)` — the first caller wins and receives sequence `0`
2. Concurrent callers fall through to `Cache::increment($key)` — atomic on Redis (and typically the database cache driver)

Prefer Redis (or another store with atomic `add` / `increment`). File and array cache stores are not safe for multi-process sequencing with this resolver.