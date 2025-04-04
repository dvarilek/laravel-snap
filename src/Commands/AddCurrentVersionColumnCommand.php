<?php

declare(strict_types=1);

namespace Dvarilek\LaravelSnap\Commands;

use Carbon\Carbon;
use Dvarilek\LaravelSnap\Exceptions\InvalidConfigurationException;
use Dvarilek\LaravelSnap\Models\Concerns\Snapshotable;
use Illuminate\Console\Command;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Finder\SplFileInfo;

class AddCurrentVersionColumnCommand extends Command
{

    protected $signature = 'laravel-snap:make-versionable {model? : The name of the model to add current version column to}';

    protected $description = 'Makes a selected model versionable by adding a current version column';

    /**
     * @return int
     *
     * @throws InvalidConfigurationException|FileNotFoundException
     */
    public function handle(): int
    {
        $model = $this->argument('model');

        if (! $this->modelIsValidForAddingCurrentVersionColumn($model)) {
            /* @var ?class-string<Model> $model */
            $model = $this->promptForMissingVersionableModel();

            if (! $model) {
                $this->error('Aborting...');

                return 1;
            }
        }

        /* @phpstan-ignore-next-line staticMethod.notFound */
        $currentVersionColumn = $model::getCurrentVersionColumn();
        $table = (new $model)->getTable();

        $migrationFileName = $this->createMigration($table, $currentVersionColumn);
        $this->info("Migration created: " . $migrationFileName);

        if ($this->migrateIfConfirmed($migrationFileName)) {
            $this->initializeCurrentVersionIfConfirmed($model, $currentVersionColumn);
        }

        $this->info("Success!");

        return 0;
    }

    /**
     * Prompt the developer for the missing versionable model.
     *
     * @return ?class-string<Model>
     */
    protected function promptForMissingVersionableModel(): ?string
    {
        $this->info("Finding possible models...");
        $modelDirectory = app_path('Models');

        /* @var Collection<int, class-string<Model>> $suitableModels */
        $suitableModels = collect(File::allFiles($modelDirectory))
            ->map(static::qualifyModelCandidateClassName(...))
            ->filter(static::isValidVersionableModelClass(...))
            ->values();

        if ($suitableModels->isEmpty()) {
            $this->error(sprintf('No suitable models found in the model directory: %s', $modelDirectory));

            return null;
        }

        return $this->choice(
            'Please select a model: ',
            $suitableModels->toArray(),
        );
    }

    /**
     * Determine if the provided value is a versionable model with error display.
     *
     * @param  mixed $modelCandidate
     *
     * @return bool
     * @throws InvalidConfigurationException
     */
    protected function modelIsValidForAddingCurrentVersionColumn(mixed $modelCandidate): bool
    {
        if (! is_a($modelCandidate, Model::class, true)) {
            $this->error("Invalid model provided");

            return false;
        }

        if (! in_array(Snapshotable::class, class_uses_recursive($modelCandidate))) {
            $this->error(sprintf('Invalid model provided, model %s must be a subclass of %s trait',
                $modelCandidate,
                Snapshotable::class
            ));

            return false;
        }

        /* @var ?string $currentVersionColumn  @phpstan-ignore-next-line */
        $currentVersionColumn = $modelCandidate::getCurrentVersionColumn();

        if ($currentVersionColumn === null) {
            $this->error('Current version column cannot be null');

            return false;
        }

        if (Schema::hasColumn((new $modelCandidate)->getTable(), $currentVersionColumn)) {
            $this->error('Current version column already exists');

            return false;
        }

        return true;
    }

    /**
     * Create current version column migration for provided table.
     *
     * @param string $table
     * @param string $currentVersionColumn
     *
     * @return string
     * @throws FileNotFoundException
     */
    protected function createMigration(string $table, string $currentVersionColumn): string
    {
        $timestamp = Carbon::now()->format('Y_m_d_H_i_s');
        $migrationFileName = $timestamp . '_add_current_version_column_to_' . $table . '_table.php';

        $fileSystem = new Filesystem();
        $stubPath = dirname(__DIR__, 2) . "/stubs/add_current_version_column_to_model.php.stub";
        $stubContent = $fileSystem->get($stubPath);

        $stubContent = Str::replace(
            ['SubstituteTable', 'SubstituteCurrentVersionColumn'],
            [$table, $currentVersionColumn],
            $stubContent
        );

        $completeMigrationFilePath = database_path('migrations/' . $migrationFileName);
        $fileSystem->put($completeMigrationFilePath, $stubContent);

        return $migrationFileName;
    }

    /**
     * If confirmed, runs the migration for the provided file name.
     *
     * @param  string $migrationFileName
     *
     * @return bool
     */
    protected function migrateIfConfirmed(string $migrationFileName): bool
    {
        if ($this->confirm("Do you wish to run the migration?", true)) {
            $this->call('migrate', [
                '--path' => "database/migrations/{$migrationFileName}",
            ]);

            $this->info(sprintf('Migration created: %s', $migrationFileName));

            return true;
        }

        return false;
    }

    /**
     * If confirmed, initializes the current version for all models affected by migration.
     *
     * @param  class-string<Model> $model
     * @param  string $currentVersionColumn
     *
     * @return void
     */
    protected function initializeCurrentVersionIfConfirmed(string $model, string $currentVersionColumn): void
    {
        $total = $model::query()->count();

        if ($this->confirm(sprintf('Do you wish to initialize current version to 1 for all models? Total: %d', $total), true)) {
            $model::query()->update([
                $currentVersionColumn => 1
            ]);

            $this->info(sprintf('Successfully initialized current version column: %s for %d models',
                $currentVersionColumn,
                $total
            ));
        }
    }

    /**
     * Convert the file path into a fully qualified class name.
     *
     * @param  SplFileInfo $file
     *
     * @return ?string
     */
    public static function qualifyModelCandidateClassName(SplFileInfo $file): ?string
    {
        // TODO: This is definitely not the cleanest way of doing things, consider refactoring in the future.
        $content = file_get_contents($file->getRealPath());

        $namespace = "";
        if (preg_match('/namespace\s+([^;]+)/i', $content, $matches)) {
            $namespace = $matches[1];
        }

        return $namespace . '\\' . pathinfo($file->getFilename(), PATHINFO_FILENAME);
    }

    /**
     * Determine if the provided value is a versionable model.
     *
     * @param  ?string $versionableModelCandidate
     *
     * @return bool
     * @throws InvalidConfigurationException
     */
    public static function isValidVersionableModelClass(?string $versionableModelCandidate): bool
    {
        return is_a($versionableModelCandidate, Model::class, true)
            && in_array(Snapshotable::class, class_uses_recursive($versionableModelCandidate))
            /* @phpstan-ignore-next-line */
            && ($currentVersionColumn = $versionableModelCandidate::getCurrentVersionColumn()) !== null
            && ! Schema::hasColumn((new $versionableModelCandidate)->getTable(), $currentVersionColumn);
    }
}