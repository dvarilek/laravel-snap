<?php

declare(strict_types=1);

namespace Dvarilek\CompleteModelSnapshot;

use Dvarilek\CompleteModelSnapshot\Exceptions\InvalidConfigurationException;
use Dvarilek\CompleteModelSnapshot\Models\Contracts\SnapshotContract;
use Dvarilek\CompleteModelSnapshot\Models\Snapshot;
use Dvarilek\CompleteModelSnapshot\Services\Contracts\AttributeCollectorInterface;
use Dvarilek\CompleteModelSnapshot\Services\Contracts\AttributeRestorerInterface;
use Dvarilek\CompleteModelSnapshot\Services\SnapshotAttributeCollector;
use Dvarilek\CompleteModelSnapshot\Services\SnapshotAttributeRestorer;
use Illuminate\Database\Eloquent\Model;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Spatie\LaravelPackageTools\Package;

class LaravelCompleteModelSnapshotServiceProvider extends PackageServiceProvider
{

    public function bootingPackage(): void
    {
        $this->app->bind(AttributeCollectorInterface::class, SnapshotAttributeCollector::class);

        $this->app->bind(AttributeRestorerInterface::class, SnapshotAttributeRestorer::class);
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
                ->askToStarRepoOnGitHub('dvarilek/laravel-complete-model-snapshot')
            );
    }

    /**
     * @return class-string<Model>
     * @throws InvalidConfigurationException
     */
    public static function determineSnapshotModel(): string
    {
        $snapshotModel = config('complete-model-snapshot.snapshot-model.model', Snapshot::class);

        if (! is_a($snapshotModel, Model::class, true)) {
            throw InvalidConfigurationException::snapshotModelMustBeSubtypeOfModel($snapshotModel);
        }

        if (! in_array(SnapshotContract::class, class_implements($snapshotModel))) {
            throw InvalidConfigurationException::snapshotModelMustImplementSnapshotContractInterface();
        }

        return $snapshotModel;
    }
}