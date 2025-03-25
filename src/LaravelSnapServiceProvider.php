<?php

declare(strict_types=1);

namespace Dvarilek\LaravelSnap;

use Dvarilek\LaravelSnap\Exceptions\InvalidConfigurationException;
use Dvarilek\LaravelSnap\Models\Contracts\SnapshotContract;
use Dvarilek\LaravelSnap\Models\Snapshot;
use Dvarilek\LaravelSnap\Services\Contracts\AttributeCollectorInterface;
use Dvarilek\LaravelSnap\Services\Contracts\AttributeRestorerInterface;
use Dvarilek\LaravelSnap\Services\SnapshotAttributeCollector;
use Dvarilek\LaravelSnap\Services\SnapshotAttributeRestorer;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

class LaravelSnapServiceProvider extends PackageServiceProvider
{

    public function bootingPackage(): void
    {
        $this->app->bind(AttributeCollectorInterface::class, SnapshotAttributeCollector::class);

        $this->app->bind(AttributeRestorerInterface::class, SnapshotAttributeRestorer::class);
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-snap')
            ->hasConfigFile('laravel-snap')
            ->discoversMigrations()
            ->hasInstallCommand(fn (InstallCommand $command) => $command
                ->setName('laravel-snap:install')
                ->publishMigrations()
                ->askToRunMigrations()
                ->askToStarRepoOnGitHub('dvarilek/laravel-snap')
            );
    }

    /**
     * @return class-string<Model>
     * @throws InvalidConfigurationException
     */
    public static function determineSnapshotModel(): string
    {
        /** @var class-string $snapshotModel */
        $snapshotModel = config('laravel-snap.snapshot-model.model', Snapshot::class);

        if (! is_a($snapshotModel, Model::class, true)) {
            throw InvalidConfigurationException::snapshotModelMustBeSubtypeOfModel($snapshotModel);
        }

        if (! in_array(SnapshotContract::class, class_implements($snapshotModel))) {
            throw InvalidConfigurationException::snapshotModelMustImplementSnapshotContractInterface();
        }

        return $snapshotModel;
    }
}