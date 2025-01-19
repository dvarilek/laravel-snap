<?php

namespace Dvarilek\LaravelSnapshotTree;

use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

class LaravelSnapshotTreeServiceProvider extends PackageServiceProvider
{

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