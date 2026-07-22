# Testing

You may wish to override the ID generator in test environments. Swap out the `IdentifierResolver` with your own:

```php
use BradieTilley\Snowflake\Snowflake;
use BradieTilley\Snowflake\IdentifierResolvers\SnowflakeIdentifierResolver;

Snowflake::identifierResolver(function (int $timeSinceConfiguredEpoch, int $sequence, string|null $group) {
    if ($group === 'something') {
        return 1000000000000000001;
    }

    return (new SnowflakeIdentifierResolver())->identifier(...func_get_args());
});

Snowflake::id('something'); // 1000000000000000001 custom ID
Snowflake::id();            // 9345345346346345323 regular ID
```

## Laravel

Laravel projects can enable sequential, predictable IDs via config instead of a custom resolver. See [Laravel integration](../laravel/README.md#testing).
