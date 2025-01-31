<?php

use BradieTilley\Snowflake\Snowflake;

test('snowflake ids can be generated', function () {
    $id = Snowflake::id();

    expect($id)->toMatch('/^\d{19}$/');
});

test('snowflake ids are never the same and are always sequential', function () {
    $all = [];

    foreach (range(1, 100) as $i) {
        $all[] = Snowflake::id();
    }

    $sorted = $all;
    sort($sorted);
    $sorted = array_unique($sorted);

    expect($sorted)->toBe($all);
});

test('snowflake ids can have the relative epoch start date configured', function () {
    $now = (new DateTime())->format('Y-m-d H:i:s');
    usleep(100);

    Snowflake::configure($now, 1, 1);
    expect((int) Snowflake::id())->toBeGreaterThan(100)->toBeLessThan(10000000000);

    $lastYear = (new DateTime('now'))->modify('-1 year')->format('Y-m-d H:i:s');
    Snowflake::configure($lastYear, 2, 3);
    expect($id = Snowflake::id())->toMatch('/^\d{18}$/');

    $data = (object) Snowflake::parse($id);
    expect($data)
        ->worker->toBe(3)
        ->cluster->toBe(2)
        ->datetime->toBe($now);
});

test('snowflake ids can support ids generated 30 years from epoch', function () {
    $now = (new DateTime())->format('Y-m-d H:i:s');

    $epoch = (new DateTime('now'))->modify('-10 years')->format('Y-m-d H:i:s');
    Snowflake::configure($epoch, 1, 1);
    expect(Snowflake::id())->toMatch('/^\d{19}$/');

    $epoch = (new DateTime('now'))->modify('-20 years')->format('Y-m-d H:i:s');
    Snowflake::configure($epoch, 1, 1);
    expect(Snowflake::id())->toMatch('/^\d{19}$/');

    $epoch = (new DateTime('now'))->modify('-30 years')->format('Y-m-d H:i:s');
    Snowflake::configure($epoch, 1, 1);
    expect($id = Snowflake::id())->toMatch('/^\d{19}$/');

    $epoch = (new DateTime('now'))->modify('-35 years')->format('Y-m-d H:i:s');
    Snowflake::configure($epoch, 1, 1);
    expect($id = Snowflake::id())->toMatch('/^\d{19}$/');

    expect(Snowflake::parse($id))
        ->worker->toBe(1)
        ->cluster->toBe(1)
        ->epoch->toBe(strtotime($epoch) * 1000 * 1000)
        ->datetime->toBe($now);

    /**
     * 36 years = overflow
     */
    $epoch = (new DateTime('now'))->modify('-36 years')->format('Y-m-d H:i:s');
    Snowflake::configure($epoch, 1, 1);
    expect($id = Snowflake::id())->toMatch('/^-\d{19}$/');
});
