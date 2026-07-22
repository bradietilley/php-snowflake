# Concurrency

Within a single process, it is nearly impossible for two Snowflake IDs to share the same timestamp. In high-concurrency environments, however, multiple IDs can land in the same microsecond. Sequencing resolves those collisions.

Avoiding shared sequencing is preferred when possible (it reduces coordination cost). Prefer **unique worker ids** per process/machine so an in-memory sequence is enough.

## Default: memory resolver

By default, PHP Snowflake uses a `MemorySequenceResolver` (in-process only). That is the fast path and is safe when:

- A single process generates IDs, or
- Each process has a **unique** worker (and/or cluster) id

```php
use BradieTilley\Snowflake\Snowflake;

Snowflake::configure('2025-01-01 00:00:00', cluster: 1, worker: $uniqueWorkerId);
Snowflake::id();
```

## File resolver (opt-in)

For multi-process apps that share the same worker id without Redis, opt into a file lock:

```php
use BradieTilley\Snowflake\Snowflake;
use BradieTilley\Snowflake\SequenceResolvers\FileSequenceResolver;

$file = __DIR__.'/snowflake-concurrency.json';
Snowflake::sequenceResolver(new FileSequenceResolver($file));

Snowflake::id(); // unique across processes that share this file
```

## Laravel cache resolver (opt-in)

Set `snowflake.sequencing.resolver` to auto-register a cache-backed sequencer. See [Laravel integration](../laravel/README.md#concurrency).

```php
// config/snowflake.php
'sequencing' => [
    'resolver' => \BradieTilley\Snowflake\Laravel\SequenceResolvers\LaravelSequenceResolver::class,
    'store' => env('SNOWFLAKE_CACHE_STORE'), // Redis recommended
],
```

`LaravelSequenceResolver` does **not** take a cache lock. For each microsecond timestamp it:

1. Attempts `Cache::add($key, …)` — the first caller wins and receives sequence `0`
2. Concurrent callers fall through to `Cache::increment($key)` — atomic on Redis (and typically the database cache driver)

Prefer Redis (or another store with atomic `add` / `increment`). File and array cache stores are not safe for multi-process sequencing with this resolver.
