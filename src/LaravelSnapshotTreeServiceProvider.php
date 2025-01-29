<?php

declare(strict_types=1);

namespace Dvarilek\LaravelSnapshotTree;

use Dvarilek\LaravelSnapshotTree\Services\Contracts\AttributeCollectorInterface;
use Dvarilek\LaravelSnapshotTree\Services\SnapshotAttributeCollector;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

class LaravelSnapshotTreeServiceProvider extends PackageServiceProvider
{

    public function bootingPackage(): void
    {
        $this->app->bind(AttributeCollectorInterface::class, SnapshotAttributeCollector::class);
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-snapshot-tree')
            ->hasConfigFile()
            ->discoversMigrations()
            ->hasInstallCommand(fn (InstallCommand $command) => $command
                ->publishMigrations()
                ->askToRunMigrations()
                ->askToStarRepoOnGitHub('dvarilek/laravel-snapshot-tree')
            );
    }
}