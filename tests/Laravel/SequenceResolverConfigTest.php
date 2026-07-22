<?php

use BradieTilley\Snowflake\Laravel\SequenceResolvers\LaravelSequenceResolver;
use BradieTilley\Snowflake\Laravel\SnowflakeGenerator;
use BradieTilley\Snowflake\Snowflake;
use Workbench\App\Models\User;

test('laravel leaves the memory sequence resolver when sequencing.resolver is null', function () {
    config([
        'snowflake.sequencing.resolver' => null,
    ]);

    $user = User::create([
        'name' => 'Test',
        'email' => 'memory-'.random_int(1, 999999999).'@test.com',
        'password' => '',
    ]);

    expect($user->id)->toBeString()->toMatch('/^\d{17,19}$/');
});

test('laravel auto-registers sequencing.resolver from config', function () {
    config([
        'snowflake.sequencing.resolver' => LaravelSequenceResolver::class,
        'cache.default' => 'array',
    ]);

    // Fresh generator instance after config change (singleton may already be booted).
    app()->forgetInstance(SnowflakeGenerator::class);
    Snowflake::reset();

    SnowflakeGenerator::make()->id(User::class);

    $reflection = new ReflectionClass(Snowflake::class);
    $property = $reflection->getProperty('sequenceResolver');
    $property->setAccessible(true);

    expect($property->getValue())->toBeInstanceOf(LaravelSequenceResolver::class);
});
