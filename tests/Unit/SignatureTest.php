<?php

use BradieTilley\Snowflake\Exceptions\SnowflakeException;
use BradieTilley\Snowflake\SequenceResolvers\MemorySequenceResolver;
use BradieTilley\Snowflake\Snowflake;

beforeEach(fn () => Snowflake::reset());

test('default signature is 5 worker, 5 cluster, 3 sequence bits', function () {
    expect(Snowflake::workerIdBits())->toBe(5)
        ->and(Snowflake::clusterIdBits())->toBe(5)
        ->and(Snowflake::sequenceBits())->toBe(3)
        ->and(Snowflake::maxSequence())->toBe(7);

    $id = Snowflake::id();
    $parsed = Snowflake::parse($id);

    expect($parsed['worker'])->toBe(1)
        ->and($parsed['cluster'])->toBe(1)
        ->and($parsed['sequence'])->toBeGreaterThanOrEqual(0)
        ->and($parsed['sequence'])->toBeLessThanOrEqual(7);
});

test('configureSignature derives sequence bits from worker and cluster bits', function () {
    Snowflake::configureSignature(workerIdBits: 10, clusterIdBits: 0);

    expect(Snowflake::workerIdBits())->toBe(10)
        ->and(Snowflake::clusterIdBits())->toBe(0)
        ->and(Snowflake::sequenceBits())->toBe(3)
        ->and(Snowflake::maxSequence())->toBe(7);

    Snowflake::configure('2025-01-01 00:00:00', cluster: 0, worker: 1023);

    $parsed = Snowflake::parse(Snowflake::id());

    expect($parsed)
        ->cluster->toBe(0)
        ->worker->toBe(1023);
});

test('configureSignature with zero worker and cluster bits maximises sequence bits', function () {
    Snowflake::configureSignature(workerIdBits: 0, clusterIdBits: 0);

    expect(Snowflake::sequenceBits())->toBe(13)
        ->and(Snowflake::maxSequence())->toBe(8191);

    $parsed = Snowflake::parse(Snowflake::id());

    expect($parsed)
        ->cluster->toBe(0)
        ->worker->toBe(0);
});

test('configureSignature rejects invalid bit widths', function (int $worker, int $cluster) {
    Snowflake::configureSignature($worker, $cluster);
})->throws(SnowflakeException::class)->with([
    [11, 0],
    [0, 11],
    [6, 5],
    [-1, 0],
]);

test('configureSignature cannot be called after the first id is generated', function () {
    Snowflake::id();

    Snowflake::configureSignature(4, 4);
})->throws(SnowflakeException::class);

test('configure rejects worker or cluster ids that do not fit the signature', function () {
    Snowflake::configureSignature(workerIdBits: 2, clusterIdBits: 2);

    Snowflake::configure('2025-01-01 00:00:00', cluster: 4, worker: 0);
})->throws(SnowflakeException::class);

test('reset restores default signature and allows reconfiguration', function () {
    Snowflake::configureSignature(0, 0);
    Snowflake::id();

    Snowflake::reset();

    expect(Snowflake::sequenceBits())->toBe(3);

    Snowflake::configureSignature(8, 2);
    expect(Snowflake::sequenceBits())->toBe(3)
        ->and(Snowflake::workerIdBits())->toBe(8)
        ->and(Snowflake::clusterIdBits())->toBe(2);
});

test('default memory sequence resolver produces unique ids in-process', function () {
    $ids = [];

    foreach (range(1, 1000) as $i) {
        $ids[] = Snowflake::id();
    }

    expect($ids)->toHaveCount(1000)
        ->and(array_unique($ids))->toHaveCount(1000);
});

test('memory sequence resolver can be set explicitly', function () {
    Snowflake::sequenceResolver(new MemorySequenceResolver());

    expect(Snowflake::id())->toMatch('/^\d+$/');
});
