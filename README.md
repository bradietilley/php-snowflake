# Laravel Snowflake

Snowflake IDs in Laravel

![Static Analysis](https://github.com/bradietilley/php-snowflake/workflows/static.yml/badge.svg)
![Tests](https://github.com/bradietilley/php-snowflake/workflo/tests.yml/badge.svg)
![PHP Version](https://img.shields.io/badge/PHP%20Version-%E2%89%A58.4-4F5B93)

## Introduction

PHP-Snowflake is a high-precision Snowflake ID generator designed for applications that prioritize timestamp accuracy over extensive sequencing. Unlike traditional auto-increment IDs or cumbersome UUIDs/ULIDs, this package leverages a microsecond-precise Snowflake implementation, practically ensuring unique timestamps per thread without even needing sequencing. By allocating 50 bits to the timestamp, it sacrifices most sequencing bits while still allowing up to 16 IDs per microsecond (4-bit sequence). Given that a single Snowflake ID generation takes around 50µs in tests, the likelihood of collision within the same microsecond is negligible. With a compact schema of timestamp(50) + cluster(5) + worker(5) + sequence(4), PHP-Snowflake offers a balanced approach to distributed ID generation with both speed and uniqueness in mind.

### Structure

**timestamp(50):** The time portion

This first segment offers 2^50 values (1.1258999068e15). There's approximately 31,557,600 seconds per year, so this equates to 35,677,615 years using second-precision IDs. A lot can happen in a second, and still a lot can happen within a millisecond, so divide this by 1,000,000 and you get 35.677615 years supporting microsecond precision timestamps.

**cluster(5):** Identifying the region, datacenter or server.

The next segment offers 2^5 values (32). This is enough for most small and medium-sized applications, and even large-scale applications if configured correctly.

**worker(5):** Identifying the worker, process or thread.

The next segment offers 2^5 values (32). This is enough for most small and medium-sized applications, and even large-scale applications if configured correctly.

**sequence(4):** IDs generated within the same microsecond

The final segment offers 2^4 values (16). These values are reserved to handle same-microsecond IDs that may be generated, albeit insanely unlikely. This is approximately 800x as many IDs than what a macbook running PHP 8.4 can generate within a test environment.

### Resources

https://en.wikipedia.org/wiki/Snowflake_ID


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

### Understanding the Configuration Parameters

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

## Author

- [Bradie Tilley](https://github.com/bradietilley)
