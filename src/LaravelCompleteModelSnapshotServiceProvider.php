<?php

declare(strict_types=1);

namespace Dvarilek\CompleteModelSnapshot;

use Dvarilek\CompleteModelSnapshot\Services\Contracts\AttributeCollectorInterface;
use Dvarilek\CompleteModelSnapshot\Services\SnapshotAttributeCollector;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

class LaravelCompleteModelSnapshotServiceProvider extends PackageServiceProvider
{

    public function bootingPackage(): void
    {
        $this->app->bind(AttributeCollectorInterface::class, SnapshotAttributeCollector::class);
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-complete-model-snapshot')
            ->hasConfigFile()
            ->discoversMigrations()
            ->hasInstallCommand(fn (InstallCommand $command) => $command
                ->publishMigrations()
                ->askToRunMigrations()
                ->askToStarRepoOnGitHub('dvarilek/complete-model-snapshot')
            );
    }
}