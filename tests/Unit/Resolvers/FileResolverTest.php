<?php

use BradieTilley\Snowflake\SequenceResolvers\FileResolver;
use BradieTilley\Snowflake\Snowflake;

beforeEach(function () {
    $this->file = __DIR__.'/file-resolver-test.json';
    file_put_contents($this->file, '');
});

afterEach(function () {
    @unlink($this->file);
    Snowflake::timestampResolver(null);
    Snowflake::sequenceResolver(null);
});

test('a file resolver can resolve a sequence', function () {
    $times = [
        1738450629796380011, // 5740539453880754441 ID
        1738450629796380011, // 5740539453880754442 ID
        1738450629796380011, // 5740539453880754443 ID
        1738450629796380011, // 5740539453880754444 ID
        1738450629796380011, // 5740539453880754445 ID
        1738450629796380011, // 5740539453880754446 ID
        1738450629796380011, // 5740539453880754447 ID
        1738450629796380011, // SEQUENCE IS EXHAUSTED THOUGH SO SLEEP REQUIRED
        1738450629796380012, // 5740539453880762633 - NEW ID
    ];

    Snowflake::timestampResolver(function () use (&$times) {
        return array_shift($times);
    });
    Snowflake::sequenceResolver(new FileResolver($this->file));

    $sequence = [
        '5740539453880754441',
        '5740539453880754442',
        '5740539453880754443',
        '5740539453880754444',
        '5740539453880754445',
        '5740539453880754446',
        '5740539453880754447',
        '5740539453880762633', // new microsecond = new sequencing
    ];

    foreach ($sequence as $order) {
        expect(Snowflake::id())->toBe($order);
    }
});
