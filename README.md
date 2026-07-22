# PHP Snowflake

Snowflake IDs in PHP, with optional first-class Laravel integration.

![Static Analysis](https://github.com/bradietilley/php-snowflake/actions/workflows/static.yml/badge.svg)
![Tests](https://github.com/bradietilley/php-snowflake/actions/workflows/tests.yml/badge.svg)
![PHP Version](https://img.shields.io/badge/PHP%20Version-%E2%89%A58.4-4F5B93)
![Laravel Version](https://img.shields.io/badge/Laravel%20Version-13.x-F9322C)

## Documentation

Full documentation is available at [bradietilley.dev/php-snowflake](https://bradietilley.dev/php-snowflake).

## Installation

```bash
composer require bradietilley/php-snowflake
```

```php
use BradieTilley\Snowflake\Snowflake;

Snowflake::configure('2025-01-01 00:00:00', cluster: 1, worker: 1);

echo Snowflake::id(); // 9048372019229466888
```

See the [documentation](https://bradietilley.dev/php-snowflake) for configuration, concurrency, testing, and Laravel integration.

## Credits

- [Bradie Tilley](https://github.com/bradietilley)
