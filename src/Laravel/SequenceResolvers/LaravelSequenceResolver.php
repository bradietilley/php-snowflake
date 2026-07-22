<?php

declare(strict_types=1);

namespace BradieTilley\Snowflake\Laravel\SequenceResolvers;

use BradieTilley\Snowflake\SequenceResolvers\SequenceResolver;
use Illuminate\Cache\CacheManager;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Facades\Cache;

class LaravelSequenceResolver implements SequenceResolver
{
    protected string $prefix;

    protected Repository $cache;

    protected Lock $lock;

    protected int $wait;

    public function __construct(CacheManager $cache)
    {
        /** @var ?string $store */
        $store = config('snowflake.sequencing.store');
        $this->cache = $cache->store($store);

        /** @var string $prefix */
        $prefix = config('snowflake.sequencing.prefix', '');
        $this->prefix = $prefix;

        /** @var int $expire */
        $expire = (int) config('snowflake.sequencing.lock_expiry', 5);
        $this->lock = Cache::lock("{$prefix}_lock", $expire);

        /** @var int $wait */
        $wait = (int) config('snowflake.sequencing.lock_wait', 6);
        $this->wait = $wait;
    }

    public function sequence(int $currentTime): int
    {
        $key = $this->prefix.$currentTime;

        return (int) $this->lock->block($this->wait, function () use ($key) {
            if ($this->cache->add($key, 1, 10)) {
                return 0;
            }

            return $this->cache->increment($key) | 0;
        });
    }
}
