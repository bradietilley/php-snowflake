# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com), and this project adheres to [Semantic Versioning](https://semver.org).

## v2.0.0

### Added

- Merged the Laravel integration (previously the standalone `bradietilley/laravel-snowflake` package) into this package under the `BradieTilley\Snowflake\Laravel\` namespace.
- `HasSnowflake` Eloquent trait for automatically assigning Snowflake IDs to models.
- `SnowflakeServiceProvider` (auto-discovered) and a publishable `snowflake` config file.
- `LaravelSequenceResolver` for cache-based concurrency (atomic `add` + `increment`, no lock), and `SequentialIdentifierResolver` for predictable sequential IDs in tests.
- `Snowflake::reset()` to clear all static configuration and resolvers, primarily to avoid global state leaking between tests.
- `Snowflake::configureSignature($workerIdBits, $clusterIdBits)` to trade cluster/worker capacity for sequence bits (sequence bits are derived; minimum 3). The signature freezes after the first generated ID.

### Changed

- The Laravel integration is fully opt-in. The core Snowflake generator has no framework dependency; `illuminate/*` packages are declared as `suggest` and dev-only requirements, so non-Laravel consumers are unaffected.
- The Laravel integration targets **Laravel 13** (`^13.0`). This is developed and tested against `orchestra/testbench: ^11.0`.
- README now documents both standalone and Laravel usage in a single place.
- Default sequence resolver is now `MemorySequenceResolver` (was `FileSequenceResolver`). Opt into file or cache coordination explicitly.
- Laravel `snowflake.sequencing.resolver` defaults to `null` (memory). Set it to `LaravelSequenceResolver::class` (or another resolver) to auto-register on boot.

### Fixed

- The Laravel cache-based sequence resolver is registered when configured. The previous `laravel-snowflake` package defined `snowflake.sequencing.resolver` but never wired it into `Snowflake::sequenceResolver()`. The `SnowflakeGenerator` now reads that config value and registers the resolver via the container when set.
- `LaravelSequenceResolver` no longer takes a global cache lock on every ID; it uses atomic `add` + `increment` per microsecond key.
- Resolved a PHPStan (max level) error in `SequentialIdentifierResolver::identifier()` where a nullable `$group` was used as an array key.

### Breaking Changes

- Users of the old `bradietilley/laravel-snowflake` package must update namespaces from `BradieTilley\Snowflakes\...` to `BradieTilley\Snowflake\Laravel\...`. For example, `BradieTilley\Snowflakes\Eloquent\HasSnowflake` becomes `BradieTilley\Snowflake\Laravel\Eloquent\HasSnowflake`.
- The service provider was renamed from `BradieTilley\Snowflakes\SnowflakesServiceProvider` to `BradieTilley\Snowflake\Laravel\SnowflakeServiceProvider`. Auto-discovery handles this automatically; only manual provider registrations need updating.
- The Laravel integration now requires **Laravel 13** (`^13.0`). Laravel 11 and 12 are no longer supported. The `bradietilley/laravel-snowflake` v1.x line remains available for Laravel 11/12 projects.
- Default sequence resolver is in-memory, not file-based. Multi-process apps sharing a worker id must call `Snowflake::sequenceResolver(...)` or set Laravel `sequencing.resolver`.
- Laravel no longer enables `LaravelSequenceResolver` by default; set `snowflake.sequencing.resolver` explicitly.
- Runtime bit widths are no longer read from `Snowflake::WORKER_ID_BITS` / `CLUSTER_ID_BITS` / `SEQUENCE_BITS` / `MAX_SEQUENCE` constants — use `configureSignature()` and the `workerIdBits()` / `clusterIdBits()` / `sequenceBits()` / `maxSequence()` getters.
