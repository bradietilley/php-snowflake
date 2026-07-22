<?php

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
         * Sequence resolver class, or null to use the package default
         * (MemorySequenceResolver — in-process only).
         *
         * For cross-process uniqueness with a shared worker id, set this to
         * LaravelSequenceResolver::class (Redis/cache recommended).
         *
         * @var class-string<\BradieTilley\Snowflake\SequenceResolvers\SequenceResolver>|null
         */
        'resolver' => null,

        /**
         * Cache store used by LaravelSequenceResolver.
         *
         * Prefer a store with atomic add (SET NX) and increment, such as Redis.
         */
        'store' => env('SNOWFLAKE_CACHE_STORE', null),

        /**
         * Cache key prefix to ensure keys don't clash with other keys
         */
        'prefix' => env('SNOWFLAKE_CACHE_PREFIX', ''),
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
         * Cluster id — must fit the configured cluster bit width
         * (default signature: 5 bits → 0–31).
         *
         * @var int
         */
        'cluster' => env('SNOWFLAKE_CLUSTER', 1),

        /**
         * Worker id within the cluster — must fit the configured worker bit width
         * (default signature: 5 bits → 0–31).
         *
         * @var int
         */
        'worker' => env('SNOWFLAKE_WORKER', 1),
    ],
];
