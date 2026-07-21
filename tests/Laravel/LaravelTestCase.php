<?php

namespace Tests\Laravel;

use BradieTilley\Snowflake\Laravel\SnowflakeServiceProvider;
use BradieTilley\Snowflake\Snowflake;
use Illuminate\Foundation\Providers\ConsoleSupportServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\Concerns\WithWorkbench;
use Orchestra\Testbench\TestCase as TestbenchTestCase;

abstract class LaravelTestCase extends TestbenchTestCase
{
    use RefreshDatabase;
    use WithWorkbench;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('migrate');
    }

    protected function tearDown(): void
    {
        // Prevent Laravel-configured static state from leaking into other tests.
        Snowflake::reset();

        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [
            SnowflakeServiceProvider::class,
            ConsoleSupportServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
