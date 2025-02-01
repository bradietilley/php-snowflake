<?php

declare(strict_types=1);

namespace BradieTilley\Snowflake\TimestampResolvers;

use BradieTilley\Snowflake\EpochNanoseconds;

class MicrosecondTimestampResolver implements TimestampResolver
{
    public function timestamp(): int
    {
        return (int) (EpochNanoseconds::now() / 1_000);
    }
}
