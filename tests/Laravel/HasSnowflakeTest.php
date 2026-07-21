<?php

use Workbench\App\Models\User;

test('a model with HasSnowflakes will automatically record a snowflake ID', function () {
    $user = User::create([
        'name' => 'Test',
        'email' => 'test@test.com',
        'password' => '',
    ]);

    expect($user->id)->toBeString()->toMatch('/^\d{17,19}$/');
});

test('a model with HasSnowflakes can create sequential IDs when testing', function () {
    config([
        'snowflakes.testing' => true,
    ]);

    $createUser = fn () => User::create([
        'name' => 'Test',
        'email' => 'test'.random_int(1000000, 9999999999999).'@test.com',
        'password' => '',
    ]);

    $user = $createUser();
    expect($user->id)->toBe('9000000000000000001');

    $user = $createUser();
    expect($user->id)->toBe('9000000000000000002');

    $user = $createUser();
    expect($user->id)->toBe('9000000000000000003');
});

test('a model with HasSnowflakes can create sequential IDs when testing - resetting between tests', function () {
    config([
        'snowflakes.testing' => true,
    ]);

    $createUser = fn () => User::create([
        'name' => 'Test',
        'email' => 'test'.random_int(1000000, 9999999999999).'@test.com',
        'password' => '',
    ]);

    $user = $createUser();
    expect($user->id)->toBe('9000000000000000001');
});
