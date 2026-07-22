<?php

declare(strict_types=1);

namespace BradieTilley\Snowflake\IdentifierResolvers;

use BradieTilley\Snowflake\Snowflake;

class SnowflakeIdentifierResolver implements IdentifierResolver
{
    public function identifier(int $time, int $sequence, string|null $group = null): int
    {
        $workerIdLeftShift = Snowflake::sequenceBits();
        $datacenterIdLeftShift = Snowflake::workerIdBits() + Snowflake::sequenceBits();
        $timestampLeftShift = Snowflake::ID_BITS - Snowflake::TIMESTAMP_BITS;

        return ($time << $timestampLeftShift)
            | (Snowflake::$cluster << $datacenterIdLeftShift)
            | (Snowflake::$worker << $workerIdLeftShift)
            | ($sequence);
    }
}
