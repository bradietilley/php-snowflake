<?php

declare(strict_types=1);

namespace BradieTilley\Snowflake;

use BradieTilley\Snowflake\Exceptions\SnowflakeException;
use BradieTilley\Snowflake\IdentifierResolvers\IdentifierResolver;
use BradieTilley\Snowflake\IdentifierResolvers\SnowflakeIdentifierResolver;
use BradieTilley\Snowflake\SequenceResolvers\MemorySequenceResolver;
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

    public const int ID_BITS = 63;

    public const int TIMESTAMP_BITS = 50;

    public const int NODE_BITS = self::ID_BITS - self::TIMESTAMP_BITS;

    public const int DEFAULT_WORKER_ID_BITS = 5;

    public const int DEFAULT_CLUSTER_ID_BITS = 5;

    public const int DEFAULT_SEQUENCE_BITS = 3;

    public const int MIN_SEQUENCE_BITS = 3;

    protected static ?int $epoch = null;

    public static int $worker = self::DEFAULT_WORKER_ID;

    public static int $cluster = self::DEFAULT_CLUSTER_ID;

    protected static int $workerIdBits = self::DEFAULT_WORKER_ID_BITS;

    protected static int $clusterIdBits = self::DEFAULT_CLUSTER_ID_BITS;

    protected static int $sequenceBits = self::DEFAULT_SEQUENCE_BITS;

    protected static int $maxSequence = (1 << self::DEFAULT_SEQUENCE_BITS) - 1;

    protected static bool $signatureFrozen = false;

    protected static Closure|SequenceResolver|null $sequenceResolver = null;

    protected static Closure|TimestampResolver|null $timestampResolver = null;

    protected static Closure|IdentifierResolver|null $identifierResolver = null;

    public static function workerIdBits(): int
    {
        return self::$workerIdBits;
    }

    public static function clusterIdBits(): int
    {
        return self::$clusterIdBits;
    }

    public static function sequenceBits(): int
    {
        return self::$sequenceBits;
    }

    public static function maxSequence(): int
    {
        return self::$maxSequence;
    }

    /**
     * Configure the node bit layout. Sequence bits are derived as
     * NODE_BITS - workerIdBits - clusterIdBits (minimum 3).
     *
     * Must be called before the first ID is generated. Changing the signature
     * after IDs exist breaks parse() and can cause collisions.
     */
    public static function configureSignature(int $workerIdBits = self::DEFAULT_WORKER_ID_BITS, int $clusterIdBits = self::DEFAULT_CLUSTER_ID_BITS): void
    {
        if (self::$signatureFrozen) {
            throw new SnowflakeException('Snowflake signature is frozen after the first ID has been generated.');
        }

        if ($workerIdBits < 0 || $workerIdBits > 10) {
            throw new SnowflakeException('workerIdBits must be between 0 and 10.');
        }

        if ($clusterIdBits < 0 || $clusterIdBits > 10) {
            throw new SnowflakeException('clusterIdBits must be between 0 and 10.');
        }

        if ($workerIdBits + $clusterIdBits > self::NODE_BITS - self::MIN_SEQUENCE_BITS) {
            throw new SnowflakeException(sprintf(
                'workerIdBits + clusterIdBits must be <= %d so sequence bits remain >= %d.',
                self::NODE_BITS - self::MIN_SEQUENCE_BITS,
                self::MIN_SEQUENCE_BITS,
            ));
        }

        self::$workerIdBits = $workerIdBits;
        self::$clusterIdBits = $clusterIdBits;
        self::$sequenceBits = self::NODE_BITS - $workerIdBits - $clusterIdBits;
        self::$maxSequence = (1 << self::$sequenceBits) - 1;
    }

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
        self::assertFitsBitWidth('cluster', $cluster, self::$clusterIdBits);
        self::assertFitsBitWidth('worker', $worker, self::$workerIdBits);

        $timestamp = strtotime($epochStart);

        self::$epoch = $timestamp * 1000 * 1000;
        self::$cluster = $cluster;
        self::$worker = $worker;
    }

    /**
     * Reset all static configuration and resolvers back to their defaults.
     *
     * Primarily useful in test suites to avoid leaking global state between
     * tests (e.g. a resolver configured by the Laravel integration bleeding
     * into a standalone test).
     */
    public static function reset(): void
    {
        self::$epoch = null;
        self::$worker = self::DEFAULT_WORKER_ID;
        self::$cluster = self::DEFAULT_CLUSTER_ID;
        self::$workerIdBits = self::DEFAULT_WORKER_ID_BITS;
        self::$clusterIdBits = self::DEFAULT_CLUSTER_ID_BITS;
        self::$sequenceBits = self::DEFAULT_SEQUENCE_BITS;
        self::$maxSequence = (1 << self::DEFAULT_SEQUENCE_BITS) - 1;
        self::$signatureFrozen = false;
        self::$sequenceResolver = null;
        self::$timestampResolver = null;
        self::$identifierResolver = null;
    }

    public static function getSequence(int $time): int
    {
        self::$sequenceResolver ??= new MemorySequenceResolver();

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
        self::$signatureFrozen = true;

        if (self::$epoch === null) {
            $maxCluster = (1 << self::$clusterIdBits) - 1;
            $maxWorker = (1 << self::$workerIdBits) - 1;

            self::configure(
                self::DEFAULT_EPOCH_START,
                min(self::DEFAULT_CLUSTER_ID, $maxCluster),
                min(self::DEFAULT_WORKER_ID, $maxWorker),
            );
        }

        $time = self::timestamp();

        while (($sequenceId = self::getSequence($time)) > self::$maxSequence) {
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
        $id = (int) $id;

        $sequenceBits = self::$sequenceBits;
        $workerIdBits = self::$workerIdBits;
        $clusterIdBits = self::$clusterIdBits;

        $sequenceMask = (1 << $sequenceBits) - 1;
        $workerMask = $workerIdBits > 0 ? (1 << $workerIdBits) - 1 : 0;
        $clusterMask = $clusterIdBits > 0 ? (1 << $clusterIdBits) - 1 : 0;

        $sequence = $id & $sequenceMask;
        $worker = $workerIdBits > 0 ? ($id >> $sequenceBits) & $workerMask : 0;
        $cluster = $clusterIdBits > 0 ? ($id >> ($sequenceBits + $workerIdBits)) & $clusterMask : 0;
        $timestamp = $id >> ($sequenceBits + $workerIdBits + $clusterIdBits);

        $epoch = static::$epoch ?? ((int) strtotime(self::DEFAULT_EPOCH_START) * 1000 * 1000);
        $datetime = date('Y-m-d H:i:s', (int) (($timestamp + $epoch) / 1000 / 1000));

        return [
            'timestamp' => $timestamp,
            'sequence' => $sequence,
            'worker' => $worker,
            'cluster' => $cluster,
            'epoch' => $epoch,
            'datetime' => $datetime,
        ];
    }

    protected static function assertFitsBitWidth(string $field, int $value, int $bits): void
    {
        $maxExclusive = 1 << $bits;

        if ($value < 0 || $value >= $maxExclusive) {
            throw new SnowflakeException(sprintf(
                '%s id %d does not fit in %d bit(s) (valid range: 0..%d).',
                $field,
                $value,
                $bits,
                $maxExclusive - 1,
            ));
        }
    }
}
