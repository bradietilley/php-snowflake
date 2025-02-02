<?php

declare(strict_types=1);

namespace BradieTilley\Snowflake\SequenceResolvers;

use BradieTilley\Snowflake\Exceptions\SnowflakeException;
use Throwable;

class FileSequenceResolver implements SequenceResolver
{
    public const int FILE_LOCK_OP = LOCK_EX;

    public const string FILE_OPEN_MODE = 'r+';

    /** @var resource|null */
    protected mixed $f = null;

    public function __construct(public string $file)
    {
    }

    public function sequence(int $currentTime): int
    {
        $this->f = null;

        try {
            $this->lock();
            // lock acquired

            /** @var array<int, int> $times */
            $times = $this->getContents();

            $this->gc($times, $currentTime);

            $times[$currentTime] ??= 0;
            /** @var array<int, int> $times */

            $times[$currentTime]++;
            /** @var array<int, int> $times */

            $this->putContents($times);

            return $times[$currentTime];
        } finally {
            $this->unlock();
        }
    }

    protected function lock(): void
    {
        $this->f = null;

        if (! file_exists($this->file)) {
            file_put_contents($this->file, '');
        }

        try {
            $f = @fopen($this->file, self::FILE_OPEN_MODE);

            if (! $f) {
                throw new SnowflakeException(sprintf('can not open this file %s', $this->file));
            }

            $this->f = $f;
        } catch (Throwable $e) {
            throw new SnowflakeException(sprintf('can not open/lock this file %s', $this->file), $e->getCode(), $e);
        }

        if (! $this->waitForLock()) {
            throw new SnowflakeException(sprintf('can not open/lock this file %s', $this->file));
        }

        // lock acquired
    }

    protected function waitForLock(): bool
    {
        /** @var resource $f */
        $f = $this->f;

        $startTime = microtime(true);

        // Attempt to acquire the lock for up to 1.5 seconds
        while ((microtime(true) - $startTime) < 1.5) {
            if (flock($f, LOCK_EX | LOCK_NB)) { // Non-blocking exclusive lock
                return true;
            }

            usleep(10); // Wait for 10 microseconds before retrying
        }

        return false;
    }

    /**
     * Get resource contents, If the contents are invalid json, return null.
     *
     * @return array<int, int>
     */
    public function getContents(): ?array
    {
        /** @var resource $f */
        $f = $this->f;

        $content = '';

        while (! feof($f)) {
            $content .= fread($f, 1024);
        }

        $content = trim($content);

        if (empty($content)) {
            return [];
        }

        $data = json_decode($content, true);
        $data = $data ? (array) $data : [];

        /** @var array<int, int> $data */
        return $data;
    }

    /**
     * @param array<int, int> $times
     */
    public function putContents(array $times): bool
    {
        /** @var resource $f */
        $f = $this->f;

        return ftruncate($f, 0) && rewind($f)
            && (fwrite($f, json_encode($times) ?: '') !== false);
    }

    protected function unlock(): void
    {
        if (is_resource($this->f)) {
            flock($this->f, LOCK_UN);
            fclose($this->f);
        }
    }

    /**
     * @param array<int, int> $times
     */
    protected function gc(array &$times, int $currentTime): void
    {
        ksort($times);
        $prevSecond = $currentTime - 1_000_000;

        foreach ($times as $timestamp => $sequence) {
            if ($timestamp >= $prevSecond) {
                break;
            }

            unset($times[$timestamp]);
        }
    }
}
