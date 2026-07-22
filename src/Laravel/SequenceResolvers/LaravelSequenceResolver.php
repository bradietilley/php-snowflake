<?php

declare(strict_types=1);

namespace BradieTilley\Snowflake\Laravel\SequenceResolvers;

use BradieTilley\Snowflake\SequenceResolvers\SequenceResolver;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Repository;

class LaravelSequenceResolver implements SequenceResolver
{
    protected string $prefix;

    protected Repository $cache;

    public function __construct(CacheManager $cache)
    {
        /** @var ?string $store */
        $store = config('snowflake.sequencing.store');
        $this->cache = $cache->store($store);

        /** @var string $prefix */
        $prefix = config('snowflake.sequencing.prefix', '');
        $this->prefix = $prefix;
    }

    public function sequence(int $currentTime): int
    {
        $key = $this->prefix.$currentTime;

        // Cache stores with atomic add (SET NX) + increment need no lock:
        // only one caller wins the initial add (sequence 0); concurrent
        // callers fall through to increment, which is atomic on Redis/DB.
        if ($this->cache->add($key, 1, 10)) {
            return 0;
        }

        return (int) $this->cache->increment($key);
    }
}
