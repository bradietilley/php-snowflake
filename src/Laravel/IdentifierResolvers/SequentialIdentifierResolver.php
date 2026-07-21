<?php

declare(strict_types=1);

namespace BradieTilley\Snowflake\Laravel\IdentifierResolvers;

use BradieTilley\Snowflake\IdentifierResolvers\IdentifierResolver;

class SequentialIdentifierResolver implements IdentifierResolver
{
    public const int START_ID = 9000000000000000000;

    /**
     * @var array<string, int> List of models and their current incrementing ID counters
     */
    protected array $models = [];

    /**
     * Resolve the singleton instance
     */
    public static function make(): self
    {
        /** @var self $instance */
        $instance = app(self::class);

        return $instance;
    }

    /**
     * Reset any recorded IDs, such as when you've truncated a DB
     * table and want the incremental test IDs to start from 1 again
     */
    public function reset(): void
    {
        $this->models = [];
    }

    public function identifier(int $time, int $sequence, ?string $group = null): int
    {
        $group ??= '';

        $count = $this->models[$group] ??= self::START_ID;
        $count++;
        $this->models[$group] = $count;

        return $count;
    }
}
