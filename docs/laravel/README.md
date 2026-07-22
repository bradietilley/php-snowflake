# Laravel integration

This package includes an optional, opt-in Laravel integration. The core Snowflake generator has **no framework dependency** — the Laravel classes only load when running inside a Laravel application.

## Installation

The Laravel integration is bundled with the package — there is nothing extra to install:

```bash
composer require bradietilley/php-snowflake
```

The service provider is auto-discovered. To customise the configuration, publish the config file:

```bash
php artisan vendor:publish --tag=snowflakes-config
```

## Preparing your schema

Your model's primary key must not auto-increment. Since `$table->id();` adds auto-increment, swap it out:

```diff
-$table->id();
+$table->bigInteger('id')->unsigned()->primary();
```

## Integrating with your models

Add the `HasSnowflake` trait to your models. It handles every aspect of a Snowflake ID:

- Automatically setting the `id` to a Snowflake ID
- Configuring the cast for `id` to `string`
- Disabling `increments` on the model
- Configuring the `keyType` to `string`

```php
use BradieTilley\Snowflake\Laravel\Eloquent\HasSnowflake;
use Illuminate\Database\Eloquent\Model;

class SomeModel extends Model
{
    use HasSnowflake;
}
```

You're all set:

```php
$model = SomeModel::create();
$model->id; // 9348975348573485734
```

## Concurrency

The integration includes a `LaravelSequenceResolver` which uses a cache repository to manage concurrent IDs within the same microsecond. It is registered automatically from the `snowflakes.sequencing.resolver` config value.

Configurable options:

- Cache store (`snowflakes.sequencing.store`)
- Cache prefix (`snowflakes.sequencing.prefix`)
- Cache lock expiry (`snowflakes.sequencing.lock_expiry`)
- Cache lock wait time (`snowflakes.sequencing.lock_wait`)

The chosen store must support cache locks.

## Testing

In tests you may want predictable, sequential IDs similar to traditional auto-incrementing IDs.

By enabling the `snowflakes.testing` configuration setting, the standard `SnowflakeIdentifierResolver` is swapped with a `SequentialIdentifierResolver`, generating realistic-length IDs that follow a standard auto-incrementing pattern.

When in testing mode, IDs can be grouped using the `$group` argument. The `$group` is automatically set to the respective model class name, so both `Product::create()` and `User::create()` generate `9000000000000000001`, then `9000000000000000002`, and so on.
