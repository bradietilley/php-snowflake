<?php

declare(strict_types=1);

namespace BradieTilley\Snowflake\IdentifierResolvers;

interface IdentifierResolver
{
    public function identifier(int $time, int $sequence, string|null $group = null): int;
}
