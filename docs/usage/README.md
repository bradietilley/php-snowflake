# Usage

## Generating a Snowflake ID

```php
echo \BradieTilley\Snowflake\Snowflake::id(); // 9048372019229466888
```

This produces a **64-bit integer**, uniquely identifying the event, with microsecond precision.

## Configuring the Snowflake generator

This package supports 35 years of IDs with microsecond precision. To maximize the lifespan of your Snowflake IDs, set the epoch (start time) to a recent timestamp.

Since the timestamp component is based on the time **elapsed since the epoch**, choosing an older epoch unnecessarily reduces the system’s usable lifespan. A fresh epoch keeps the generator valid for decades.

Configure the epoch, **cluster ID**, and **worker ID** with `configure()`:

```php
use BradieTilley\Snowflake\Snowflake;

$cluster = 1; // Example: config('app.cluster')
$worker = 1; // Example: config('app.worker')

Snowflake::configure('2025-01-01 00:00:00', $cluster, $worker);
```

### Configuration parameters

- **Epoch** (`'2025-01-01 00:00:00'`)
  - Starting point for all Snowflake IDs.
  - Generated IDs store a timestamp relative to this date.
  - The closer the epoch is to the present, the longer the system can run before hitting the timestamp limit.

- **Cluster ID** (`$cluster`)
  - Differentiates groups of workers.
  - Typically used for multiple datacenters, availability zones, or database shards.
  - Must be unique across all clusters.
  - Must fit the configured cluster bit width (default: 0–31).

- **Worker ID** (`$worker`)
  - Identifies the machine or service instance generating the ID.
  - Must be unique within a given cluster.
  - Must fit the configured worker bit width (default: 0–31).

### Bit signature

The ID layout is `[ timestamp (50) | cluster | worker | sequence ]` within 63 bits. By default that is **5 cluster + 5 worker + 3 sequence** bits.

Trade cluster/worker capacity for more sequence bits (or the reverse) with `configureSignature()` **before the first ID is generated**:

```php
use BradieTilley\Snowflake\Snowflake;

// 1024 workers, no cluster field → 3 sequence bits
Snowflake::configureSignature(workerIdBits: 10, clusterIdBits: 0);
Snowflake::configure('2025-01-01 00:00:00', cluster: 0, worker: 42);

// No cluster/worker fields → 13 sequence bits (max throughput per microsecond on one node)
Snowflake::configureSignature(workerIdBits: 0, clusterIdBits: 0);
Snowflake::configure('2025-01-01 00:00:00', cluster: 0, worker: 0);
```

Sequence bits are always derived: `13 - workerIdBits - clusterIdBits`, and must remain **≥ 3** (so `workerIdBits + clusterIdBits ≤ 10`).

The signature is frozen after the first `id()` call. Changing it later breaks `parse()` and can collide with existing IDs — treat it like the epoch.

In Laravel, call `configureSignature()` from `bootstrap/app.php` or `AppServiceProvider::register()` before any model creates IDs.

### The epoch should never change

Once the Snowflake generator is in use, do not modify the epoch. Changing it would:

- Invalidate previously generated IDs, making them non-sequential.
- Potentially cause ID collisions, as new IDs could overlap with old ones.
- Break sorting logic, since IDs would no longer be chronological.

If you must transition to a new epoch due to long-term lifespan concerns, consider introducing a new ID version alongside the existing one, or migrating old data to a format that accommodates a different epoch.

## Next steps

- [Concurrency](../concurrency/README.md) — uniqueness under parallel requests
- [Testing](../testing/README.md) — overriding ID generation in tests
- [Laravel integration](../laravel/README.md) — Eloquent models and config
