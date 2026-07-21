<?php

declare(strict_types=1);

namespace BradieTilley\Snowflake\Laravel;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SnowflakeServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('snowflakes')
            ->hasConfigFile('snowflakes');
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(SnowflakeGenerator::class, SnowflakeGenerator::class);
    }

    /**
     * The service provider lives in src/Laravel, but laravel-package-tools
     * resolves the config file relative to the base dir as "../config". Return
     * the src/ directory so "../config" resolves to the package-root config/.
     */
    protected function getPackageBaseDir(): string
    {
        return dirname(__DIR__);
    }
}
