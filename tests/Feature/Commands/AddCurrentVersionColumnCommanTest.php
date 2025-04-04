<?php

declare(strict_types=1);

use Dvarilek\LaravelSnap\Tests\Models\{TestInvalidVersionableModel, TestPendingVersionableModel, TestVersionableModel};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\File;
use Dvarilek\LaravelSnap\Models\Concerns\Snapshotable;
use Carbon\Carbon;

beforeEach(function () {
    File::cleanDirectory(database_path('migrations'));
    File::cleanDirectory(app_path('Models'));

    // Put the package stub into application context
    $stubPath = dirname(__DIR__, 3) . "/stubs/add_current_version_column_to_model.php.stub";
    File::put(database_path('stubs'), $stubPath);

    Carbon::setTestNow(Carbon::now());
});

afterEach(function () {
    File::cleanDirectory(database_path('migrations'));
    File::cleanDirectory(app_path('Models'));

    $tableName = (new TestPendingVersionableModel())->getTable();

    Schema::table($tableName, function (Blueprint $table) use ($tableName) {

        // RefreshesDatabase trait remigrates the current version column we added during some tests.
        if (Schema::hasColumn($tableName, 'current_version')) {
            $table->dropColumn('current_version');
        }
    });
});

describe('Validation', function () {
    test('command fails when no model is provided and no suitable models are found ', function () {
        $this->artisan('laravel-snap:make-versionable')
            ->expectsOutput("Invalid model provided")
            ->expectsOutput("Finding possible models...")
            ->expectsOutputToContain('No suitable models found in the model directory:')
            ->expectsOutput('Aborting...')
            ->assertExitCode(1);
    });

    test('command fails when provided model is not Snapshotable', function () {
        $model = new class extends Model {};

        $this->artisan('laravel-snap:make-versionable', [
            'model' => $model,
        ])
            ->expectsOutputToContain("must be a subclass of")
            ->expectsOutput("Finding possible models...")
            ->expectsOutputToContain('No suitable models found in the model directory:')
            ->expectsOutput('Aborting...')
            ->assertExitCode(1);
    });

    test('command fails when provided model does not have version column defined', function () {
        $model = new class extends TestPendingVersionableModel {
            public static function getCurrentVersionColumn(): ?string
            {
                return null;
            }
        };

        $this->artisan('laravel-snap:make-versionable', [
            'model' => $model,
        ])
            ->expectsOutput('Current version column cannot be null')
            ->expectsOutput("Finding possible models...")
            ->expectsOutputToContain('No suitable models found in the model directory:')
            ->expectsOutput('Aborting...')
            ->assertExitCode(1);
    });

    test('command fails when the provided model already has current version column migrated', function () {
        $this->artisan('laravel-snap:make-versionable', [
            'model' => TestVersionableModel::class,
        ])
            ->expectsOutput("Current version column already exists")
            ->expectsOutput("Finding possible models...")
            ->expectsOutputToContain('No suitable models found in the model directory:')
            ->expectsOutput('Aborting...')
            ->assertExitCode(1);
    });

    test('command displays a selection of valid models when an invalid model is provided', function () {
        File::copy((new ReflectionClass(TestPendingVersionableModel::class))->getFileName(), app_path('Models/TestPendingVersionableModel.php'));
        File::copy((new ReflectionClass(TestInvalidVersionableModel::class))->getFileName(), app_path('Models/TestInvalidVersionableModel.php'));
        File::copy((new ReflectionClass(Snapshotable::class))->getFileName(), app_path('Models/Snapshotable.php'));

        $this->artisan('laravel-snap:make-versionable')
            ->expectsOutput("Invalid model provided")
            ->expectsOutput("Finding possible models...")
            ->expectsChoice("Please select a model: ", [
                TestPendingVersionableModel::class
            ], [
                TestPendingVersionableModel::class,
            ])
            ->expectsQuestion("Do you wish to run the migration?", false)
            ->expectsOutput("Success!")
            ->assertExitCode(0);
    });
});

describe("Migration", function () {
    test('migration file gets created from stub for valid model', function () {
        $model = TestPendingVersionableModel::class;

        $this->artisan('laravel-snap:make-versionable', [
            'model' => $model
        ])
            ->expectsQuestion("Do you wish to run the migration?", false)
            ->expectsOutput("Success!")
            ->assertExitCode(0);

        $files = File::allFiles(database_path('migrations'));
        $table = (new $model)->getTable();
        $expectedMigrationFileName = Carbon::now()->format('Y_m_d_H_i_s') . '_add_current_version_column_to_' . $table . '_table.php';

        expect($files)
            ->toHaveCount(1)
            ->and($files[0]->getFilename())->toContain($expectedMigrationFileName);

        $migrationFileContents = File::get(database_path('migrations/'.$expectedMigrationFileName));

        expect($migrationFileContents)
            ->toContain("Schema::table(\"$table\"");
    });

    test('migration gets successfully migrated', function () {
        $model = TestPendingVersionableModel::class;
        $table = (new $model)->getTable();

        $this->artisan('laravel-snap:make-versionable', [
            'model' => $model
        ])
            ->expectsQuestion("Do you wish to run the migration?", true)
            ->expectsQuestion("Do you wish to initialize current version to 1 for all models? Total: 0", false)
            ->expectsOutput("Success!")
            ->assertExitCode(0);

        $migrations = DB::table('migrations')->get();
        $expectedMigrationFileName = Carbon::now()->format('Y_m_d_H_i_s') . '_add_current_version_column_to_' . $table . '_table';

        expect(collect($migrations)->pluck('migration'))
            ->contains($expectedMigrationFileName)->toBeTrue()
            ->and(Schema::hasColumn($table, 'current_version'))->toBeTrue();
    });

    test('migration gets successfully migrated and existing models current version initialized', function () {
        $model = TestPendingVersionableModel::class;

        $models = collect([
            $model::query()->create(),
            $model::query()->create()
        ]);

        $this->artisan('laravel-snap:make-versionable', [
            'model' => $model
        ])
            ->expectsQuestion("Do you wish to run the migration?", true)
            ->expectsQuestion("Do you wish to initialize current version to 1 for all models? Total: 2", true)
            ->expectsOutput("Success!")
            ->assertExitCode(0);

        $models->each(function (TestPendingVersionableModel $model) {
            expect($model->refresh()->current_version)->toBe(1);
        });
    });
});