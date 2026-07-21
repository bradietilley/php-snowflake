<?php

declare(strict_types=1);

namespace BradieTilley\Snowflake\Laravel;

use BradieTilley\Snowflake\Laravel\IdentifierResolvers\SequentialIdentifierResolver;
use BradieTilley\Snowflake\SequenceResolvers\SequenceResolver;
use BradieTilley\Snowflake\Snowflake;

class SnowflakeGenerator
{
    protected bool $booted = false;

    public static function make(): static
    {
        return app(static::class);
    }

    protected function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        if (config('snowflakes.testing')) {
            Snowflake::identifierResolver(new SequentialIdentifierResolver());
        }

        /** @var ?class-string<SequenceResolver> $resolver */
        $resolver = config('snowflakes.sequencing.resolver');

        if ($resolver !== null) {
            /** @var SequenceResolver $instance */
            $instance = app($resolver);
            Snowflake::sequenceResolver($instance);
        }

        /** @var array{ epoch: string, cluster: int, worker: int } $config */
        $config = config('snowflakes.constants', []);
        Snowflake::configure($config['epoch'], $config['cluster'], $config['worker']);
    }

    public function id(string $group): string
    {
        $this->boot();

        return Snowflake::id($group);
    }
}
