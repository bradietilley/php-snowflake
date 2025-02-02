# Laravel Snowflake

Snowflake IDs in PHP

![Static Analysis](https://github.com/bradietilley/php-snowflake/actions/workflows/static.yml/badge.svg)
![Tests](https://github.com/bradietilley/php-snowflake/actions/workflows/tests.yml/badge.svg)
![PHP Version](https://img.shields.io/badge/PHP%20Version-%E2%89%A58.4-4F5B93)

## Introduction

[Snowflake IDs](https://en.wikipedia.org/wiki/Snowflake_ID) come in various flavours. All variants of Snowflake IDs provide a distributed, time-based unique identifier system that avoids the pitfalls of traditional auto-incrementing IDs, and provides a cleaner alternative to large and unwieldy ULIDs/UUIDs. They enable efficient sorting, reduce database contention, and work well in distributed environments without requiring a single centralized coordination (to an extent at least).

PHP-Snowflake implements a high-precision Snowflake ID generation system that prioritizes timestamp accuracy over extensive sequencing. By using a microsecond-precise timestamp, it significantly reduces the chances of generating sequentially enumerable IDs, a common issue with standard Snowflake and ULID implementations when producing multiple IDs per second or millisecond. It should go without saying, but sequential IDs for public viewing are typically frowned upon.

This implementation dedicates 50 bits to the timestamp, leaving only 4 bits for sequencing, allowing up to 16 unique IDs per microsecond. The ID structure follows a compact schema:

- **Timestamp (50 bits)** – Ensures precise ordering and uniqueness.
- **Cluster (5 bits) & Worker (5 bits)** – Supports distributed ID generation.
- **Sequence (4 bits)** – Allows limited sequencing but overall minimizes predictability.

By focusing on timestamp granularity, PHP-Snowflake provides a balance between uniqueness, speed, and distributed scalability while reducing the likelihood of predictable ID sequencing.

| Feature                                   | Auto-Inc IDs        | UUIDs                     | ULIDs                      | Traditional Snowflake IDs | Snowflake IDs (This package) |
|-------------------------------------------|---------------------|---------------------------|----------------------------|---------------------------|--------------------------|
| **Easy to read/copy/relay**               | ✅ Yes              | ❌ No                      | ❌ No                      | ✅ Yes                    | ✅ Yes                   |
| **Stored as BIGINT**                      | ✅ Yes              | ❌ No (String/Binary)      | ❌ No (String/Binary)      | ✅ Yes                    | ✅ Yes                   |
| **Hides table count**                     | ❌ No               | ✅ Yes                     | ✅ Yes                     | ✅ Yes                    | ✅ Yes                   |
| **Globally scalable**                     | ❌ No               | ✅ Yes                     | ✅ Yes                     | ✅ Yes                    | ✅ Yes                   |
| **Protects enumeration...**               | ❌ No               | ✅ Yes (ms)                | ✅ Yes (ms)                | ✅ Yes (ms)               | ✅ Yes (μs)                    |
| **Sortable by time**                      | ✅ Yes              | ✅ Yes (if time-sorted)    | ✅ Yes                     | ✅ Yes                    | ✅ Yes                   |
| **Suitable for Primary/Foreign Key**      | ✅ Yes              | ❌ No                      | ❌ No                      | ✅ Yes                    | ✅ Yes                   |
| **Can be sent as number in JSON**         | ✅ Yes (until 2^53) | ❌ String                  | ❌ String                  | ❌ String                 | ❌ String                |


## Installation

```
composer require bradietilley/php-snowflake
```

## Documentation

### Generating a Snowflake ID

To generate a unique Snowflake ID, simply call:

```php
echo \BradieTilley\Snowflake\Snowflake::id(); // 9048372019229466888
```

This will produce a **64-bit integer**, uniquely identifying the event, with microsecond precision.

### Configuring the Snowflake Generator

This package supports 35 years of IDs with microsecond precision. To maximize the lifespan of your Snowflake IDs, you should set the epoch (start time) to a recent timestamp.

Since the timestamp component of the Snowflake ID is based on the time **elapsed since the epoch**, choosing an older epoch unnecessarily reduces the system’s usable lifespan. A fresh epoch ensures the ID generator will remain valid for decades.

You can configure the epoch, as well as the **cluster ID** and **worker ID**, using the configure() method:

```php
use BradieTilley\Snowflake\Snowflake;

$cluster = 1; // Example: config('app.cluster')
$worker = 1; // Example: config('app.worker')

Snowflake::configure('2025-01-01 00:00:00', $cluster, $worker);
```

**Understanding the Configuration Parameters:**

- Epoch (`'2025-01-01 00:00:00'`)
    - This defines the starting point for all Snowflake IDs.
    - All generated IDs will store a timestamp relative to this date.
    - The further in the future your epoch is, the longer the system can run before hitting the timestamp limit.

- Cluster ID (`$cluster`)
    - Used to differentiate groups of workers.
    - Typically used when running multiple datacenters, availability zones, or database shards.
    - Must be unique across all clusters.

- Worker ID (`$worker`)
    - Identifies the machine or service instance generating the ID.
    - Must be unique within a given cluster.

⚠️ Important: The Epoch Should Never Change

- Once the Snowflake generator is in use, do not modify the epoch. Changing it would:
    - Invalidate previously generated IDs, making them non-sequential.
    - Potentially cause ID collisions, as new IDs could overlap with old ones.
    - Break sorting logic, since IDs would no longer be chronological.
- If you must transition to a new epoch due to long-term system lifespan concerns, consider:
    - Introducing a new ID version system alongside the existing one.
    - Migrating old data to a new storage format that accommodates a different epoch.


### Concurrency

Within a single process, it's nearly impossible for two Snowflake IDs to have the same timestamp.
However, in high-concurrency environments where multiple requests occur simultaneously, it's possible
for multiple IDs to share the same timestamp, potentially causing conflicts. This is where sequencing
is used.

While avoiding sequencing is preferred to reduce ID predictability, it becomes necessary when generating
multiple IDs within the same microsecond.

To ensure uniqueness across concurrent requests, you should use a sequence resolver that implements
locking. While a cache-based lock may be slower than traditional database Auto Increment IDs, it enables
global scalability, where a single database column is not sufficient.

By default, PHP Snowflake will use a `FileResolver` which implements locking, however it will point to a
file within this package (for guaranteed readability from the php process). Should you need to, you can
configure the path or swap it out entirely (it's recommended to explicitly configure this).

```php
use BradieTilley\Snowflake\Snowflake;
use BradieTilley\Snowflake\SequenceResolvers\FileResolver;

$file = __DIR__.'/snowflake-concurrency.json';
Snowflake::sequenceResolver(new FileResolver($file));

Snowflake::id(); // guaranteed to be unique, even if it's the same microsecond as another process
```

See [Laravel Snowflake](https://github.com/bradietilley/laravel-snowflake) package for Laravel-specific sequence resolvers.

### Testing

You may wish to override the ID generator in test environments. You can do this by swapping out the `IdentifierResolver` with your own:

```php
Snowflake::identifierResolver(function (int $timeSinceConfiguredEpoch, int $sequence, string|null $group) {
    if ($group === 'something') {
        return 1000000000000000001;
    }

    return (new SnowflakeIdentifierResolver())->identifier(...func_get_args());
});

Snowflake::id('something'); // 1000000000000000001 custom ID
Snowflake::id();            // 9345345346346345323 regular ID
```

## Author

- [Bradie Tilley](https://github.com/bradietilley)
