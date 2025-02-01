<?php

declare(strict_types=1);

namespace BradieTilley\Snowflake\TimestampResolvers;

interface TimestampResolver
{
    public function timestamp(): int;
}
