<?php

declare(strict_types=1);

namespace BradieTilley\Snowflake;

final class EpochNanoseconds
{
    public const int BILLION = 1_000_000_000;

    public const int MILLION = 1_000_000;

    public const int THOUSAND = 1_000;

    public const int SCALE = 9;

    public const int FORMAT_DECIMALS = 3;

    protected static ?int $referenceStartHrtime = null;

    protected static ?int $referenceStartTime = null;

    /**
     * Not perfect but close enough
     */
    public static function now(): int
    {
        if (self::$referenceStartHrtime === null) {
            self::$referenceStartHrtime = hrtime(true);
            self::$referenceStartTime = time() * self::BILLION;
        }

        $elapsedTime = hrtime(true) - self::$referenceStartHrtime;
        $nanoseconds = self::$referenceStartTime + (int) $elapsedTime;

        return $nanoseconds;
    }
}
