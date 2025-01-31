<?php

declare(strict_types=1);

namespace BradieTilley\Snowflake;

class Snowflake
{
    public const string DEFAULT_EPOCH_START = '2021-02-02 00:00:00';

    public const int DEFAULT_WORKER_ID = 1;

    public const int DEFAULT_CLUSTER_ID = 1;

    public const int TIMESTAMP_BITS = 50;

    public const int WORKER_ID_BITS = 5;

    public const int CLUSTER_ID_BITS = 5;

    public const int SEQUENCE_BITS = 3;

    public const int ID_BITS = 63;

    public const int MAX_SEQUENCE = 7;

    protected static ?int $epoch = null;

    protected static ?int $lastTimestamp = null;

    protected static ?int $sequence = null;

    protected static int $worker = self::DEFAULT_WORKER_ID;

    protected static int $cluster = self::DEFAULT_CLUSTER_ID;

    public static function configure(string $epochStart, int $cluster, int $worker): void
    {
        $timestamp = strtotime($epochStart);

        self::$epoch = $timestamp * 1000 * 1000;
        self::$lastTimestamp = self::$epoch;

        self::$cluster = $cluster;
        self::$worker = $worker;
    }

    public static function getSequence(int $time): int
    {
        if (self::$lastTimestamp === $time) {
            self::$sequence++;

            return self::$sequence;
        }

        self::$sequence = 0;
        self::$lastTimestamp = $time;

        return self::$sequence;
    }

    public static function id(?string $group = null): string
    {
        return (string) self::generate();
    }

    /**
     * Generate the 64bit unique id.
     */
    protected static function generate(): int
    {
        if (self::$epoch === null) {
            self::configure(self::DEFAULT_EPOCH_START, self::DEFAULT_CLUSTER_ID, self::DEFAULT_WORKER_ID);
        }

        $time = self::timestamp();

        while (($sequenceId = self::getSequence($time)) > self::MAX_SEQUENCE) {
            usleep(1);
            $time = self::timestamp();
        }

        self::$lastTimestamp = $time;

        return self::toSnowflakeId($time - self::$epoch, $sequenceId);
    }

    public static function toSnowflakeId(int $time, int $sequence): int
    {
        $workerIdLeftShift = self::SEQUENCE_BITS;
        $datacenterIdLeftShift = self::WORKER_ID_BITS + self::SEQUENCE_BITS;
        $timestampLeftShift = self::ID_BITS - self::TIMESTAMP_BITS;

        return ($time << $timestampLeftShift)
            | (self::$cluster << $datacenterIdLeftShift)
            | (self::$worker << $workerIdLeftShift)
            | ($sequence);
    }

    /**
     * Return the now unixtime.
     */
    public static function timestamp(): int
    {
        return (int) (EpochNanoseconds::now() / 1000);
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
