<?php

declare(strict_types=1);

namespace BradieTilley\Snowflake\SequenceResolvers;

/**
 * Does not support concurrency. Not recommended for production.
 */
class MemorySequenceResolver implements SequenceResolver
{
    protected int $lastTime = 0;
    protected int $sequence = 0;

    public function sequence(int $currentTime): int
    {
        if ($this->lastTime === $currentTime) {
            return ++$this->sequence;
        }

        $this->lastTime = $currentTime;

        return $this->sequence = 0;
    }
}
