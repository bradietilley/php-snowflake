<?php

declare(strict_types=1);

namespace BradieTilley\Snowflake\SequenceResolvers;

interface SequenceResolver
{
    public function sequence(int $currentTime): int;
}
