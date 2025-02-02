<?php

declare(strict_types=1);

namespace BradieTilley\Snowflake;

use BradieTilley\Snowflake\IdentifierResolvers\IdentifierResolver;
use BradieTilley\Snowflake\IdentifierResolvers\SnowflakeIdentifierResolver;
use BradieTilley\Snowflake\SequenceResolvers\FileSequenceResolver;
use BradieTilley\Snowflake\SequenceResolvers\SequenceResolver;
use BradieTilley\Snowflake\TimestampResolvers\MicrosecondTimestampResolver;
use BradieTilley\Snowflake\TimestampResolvers\TimestampResolver;
use Closure;

class Snowflake
{
    public const string DEFAULT_EPOCH_START = '2021-02-02 00:00:00';

    public const string DEFAULT_FILE_RESOLVER_PATH = __DIR__.'/../sequence.json';

    public const int DEFAULT_WORKER_ID = 1;

    public const int DEFAULT_CLUSTER_ID = 1;

    public const int TIMESTAMP_BITS = 50;

    public const int WORKER_ID_BITS = 5;

    public const int CLUSTER_ID_BITS = 5;

    public const int SEQUENCE_BITS = 3;

    public const int ID_BITS = 63;

    public const int MAX_SEQUENCE = 7;

    protected static ?int $epoch = null;

    public static int $worker = self::DEFAULT_WORKER_ID;

    public static int $cluster = self::DEFAULT_CLUSTER_ID;

    protected static Closure|SequenceResolver|null $sequenceResolver = null;

    protected static Closure|TimestampResolver|null $timestampResolver = null;

    protected static Closure|IdentifierResolver|null $identifierResolver = null;

    /**
     * @param class-string<SequenceResolver>|SequenceResolver|Closure|null $resolver
     */
    public static function sequenceResolver(Closure|SequenceResolver|string|null $resolver): void
    {
        static::$sequenceResolver = is_string($resolver) ? new $resolver() : $resolver;
    }

    /**
     * @param class-string<TimestampResolver>|TimestampResolver|Closure|null $resolver
     */
    public static function timestampResolver(Closure|string|TimestampResolver|null $resolver): void
    {
        static::$timestampResolver = is_string($resolver) ? new $resolver() : $resolver;
    }

    /**
     * @param class-string<IdentifierResolver>|IdentifierResolver|Closure|null $resolver
     */
    public static function identifierResolver(Closure|IdentifierResolver|string|null $resolver): void
    {
        static::$identifierResolver = is_string($resolver) ? new $resolver() : $resolver;
    }

    public static function configure(string $epochStart, int $cluster, int $worker): void
    {
        $timestamp = strtotime($epochStart);

        self::$epoch = $timestamp * 1000 * 1000;
        self::$cluster = $cluster;
        self::$worker = $worker;
    }

    public static function getSequence(int $time): int
    {
        self::$sequenceResolver ??= new FileSequenceResolver(self::DEFAULT_FILE_RESOLVER_PATH);

        if (self::$sequenceResolver instanceof Closure) {
            return (int) (self::$sequenceResolver)($time);
        }

        return self::$sequenceResolver->sequence($time);
    }

    public static function id(string|null $group = null): string
    {
        return (string) self::generate($group);
    }

    /**
     * Generate the 64bit unique id.
     */
    protected static function generate(string|null $group = null): int
    {
        if (self::$epoch === null) {
            self::configure(self::DEFAULT_EPOCH_START, self::DEFAULT_CLUSTER_ID, self::DEFAULT_WORKER_ID);
        }

        $time = self::timestamp();

        while (($sequenceId = self::getSequence($time)) > self::MAX_SEQUENCE) {
            usleep(1);
            $time = self::timestamp();
        }

        $lapsed = $time - self::$epoch;

        return self::toSnowflakeId($lapsed, $sequenceId, $group);
    }

    public static function toSnowflakeId(int $time, int $sequence, string|null $group): int
    {
        static::$identifierResolver ??= new SnowflakeIdentifierResolver();

        if (static::$identifierResolver instanceof Closure) {
            return (int) (static::$identifierResolver)($time, $sequence, $group);
        }

        return static::$identifierResolver->identifier($time, $sequence, $group);
    }

    /**
     * Return the now unixtime.
     */
    public static function timestamp(): int
    {
        self::$timestampResolver ??= new MicrosecondTimestampResolver();

        if (self::$timestampResolver instanceof Closure) {
            return (int) (self::$timestampResolver)();
        }

        return self::$timestampResolver->timestamp();
    }

    /**
     * @return array{ timestamp: int, sequence: int, worker: int, cluster: int, epoch: int, datetime: string }
     */
    public static function parse(int|string $id): array
    {
        $id = decbin((int) $id);

        $clusterLeftShift = self::WORKER_ID_BITS + self::SEQUENCE_BITS;
        $timestampLeftShift = self::CLUSTER_ID_BITS + self::WORKER_ID_BITS + self::SEQUENCE_BITS;

        $binaryTimestamp = substr($id, 0, -$timestampLeftShift);
        $binarySequence = substr($id, -self::SEQUENCE_BITS);
        $binaryWorker = substr($id, -$clusterLeftShift, self::WORKER_ID_BITS);
        $binaryCluster = substr($id, -$timestampLeftShift, self::CLUSTER_ID_BITS);
        $timestamp = (int) bindec($binaryTimestamp);
        $datetime = date('Y-m-d H:i:s', ((int) (($timestamp + static::$epoch) / 1000 / 1000) | 0));

        return [
            'timestamp' => $timestamp,
            'sequence' => (int) bindec($binarySequence),
            'worker' => (int) bindec($binaryWorker),
            'cluster' => (int) bindec($binaryCluster),
            'epoch' => static::$epoch ?? (int) strtotime(self::DEFAULT_EPOCH_START),
            'datetime' => $datetime,
        ];
    }
}
