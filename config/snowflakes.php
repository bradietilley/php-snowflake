<?php

use BradieTilley\Snowflake\Laravel\SequenceResolvers\LaravelSequenceResolver;

return [
    /**
     * Use incremental IDs starting from 9000000000000000000 for predictability
     * in test environments, while using a realistically long number that shares
     * similar length requirements when serialising via JSON.
     *
     * Useful for unit testing when wanting predictable, sequential IDs.
     */
    'testing' => env('SNOWFLAKE_TESTING', false),

    'sequencing' => [
        /**
         * Choose the resolver to use for handling concurrency
         */
        'resolver' => LaravelSequenceResolver::class,

        /**
         * The LaravelCacheResolver uses Laravel caching to ensure concurrency.
         *
         * This store should be a store that supports Cache Locks.
         */
        'store' => env('SNOWFLAKE_CACHE_STORE', null),

        /**
         * Cache key prefix to ensure keys don't clash with other keys
         */
        'prefix' => env('SNOWFLAKE_CACHE_PREFIX', ''),

        /**
         * Number of seconds that the cache lock should be valid for
         */
        'lock_expiry' => env('SNOWFLAKE_CACHE_LOCK_EXPIRY', 2),

        /**
         * Number of seconds to wait for the lock before throwing an exception
         */
        'lock_wait' => env('SNOWFLAKE_CACHE_LOCK_WAIT', 3),
    ],

    /**
     * A set of constants that should realistically never change, or when they are
     * changed, appropriate measures should be made.
     */
    'constants' => [
        /**
         * The starting epoch timestamp for all timestamps to be relative to.
         *
         * A recent epoch ensures the ID generator will remain valid for 3.5 decades.
         */
        'epoch' => env('SNOWFLAKE_EPOCH', '2025-01-01 00:00:00'),

        /**
         * Identifier of the cluster (0-31)
         *
         * @var int
         */
        'cluster' => env('SNOWFLAKE_CLUSTER', 1),

        /**
         * Identifier of for the given cluster (0-31)
         *
         * @var int
         */
        'worker' => env('SNOWFLAKE_WORKER', 1),
    ],
];
