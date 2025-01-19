<?php

namespace Dvarilek\LaravelSnapshotTree\Tests;

use Dvarilek\LaravelSnapshotTree\LaravelSnapshotTreeServiceProvider;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as OrchestraTestCase;

abstract class TestCase extends OrchestraTestCase
{
    use RefreshDatabase;
    protected function setUp(): void
    {
        parent::setUp();

        $this->prepareDatabase();
    }

    protected function getPackageProviders($app): array
    {
        return [
            LaravelSnapshotTreeServiceProvider::class,
        ];
    }

    protected function prepareDatabase(): void
    {
        $migration = require __DIR__.'/../database/migrations/create_model_snapshots_table.php';

        $migration->up();
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
    }

    protected function defineEnvironment($app): void
    {
        tap($app['config'], function (Repository $config) {
            $config->set('database.default', 'testbench');
            $config->set('database.connections.testbench', [
                'driver'   => 'sqlite',
                'database' => ':memory:',
                'prefix'   => '',
            ]);

            $config->set([
                'queue.batching.database' => 'testbench',
                'queue.failed.database' => 'testbench',
            ]);
        });
    }

}
