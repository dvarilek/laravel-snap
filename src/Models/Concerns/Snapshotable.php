<?php

declare(strict_types=1);

namespace Dvarilek\LaravelSnap\Models\Concerns;

use Dvarilek\LaravelSnap\DTO\Contracts\VirtualAttribute;
use Dvarilek\LaravelSnap\Exceptions\InvalidConfigurationException;
use Dvarilek\LaravelSnap\Exceptions\InvalidSnapshotException;
use Dvarilek\LaravelSnap\Exceptions\SnapshotableModelException;
use Dvarilek\LaravelSnap\LaravelSnapServiceProvider;
use Dvarilek\LaravelSnap\Models\Contracts\SnapshotContract;
use Dvarilek\LaravelSnap\Services\Contracts\AttributeCollectorInterface;
use Dvarilek\LaravelSnap\Services\Contracts\AttributeRestorerInterface;
use Dvarilek\LaravelSnap\Support\SnapshotValidator;
use Dvarilek\LaravelSnap\ValueObjects\SnapshotDefinition;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Closure;
use Illuminate\Events\QueuedClosure;
use Illuminate\Support\Facades\Cache;

/**
 * @mixin Model
 *
 * @phpstan-ignore trait.unused
 */
trait Snapshotable
{

    /**
     * Configure what should and shouldn't be captured in a snapshot.
     *
     * @return SnapshotDefinition
     */
    abstract public static function getSnapshotDefinition(): SnapshotDefinition;

    /**
     * Return name of column used for storing the current model version.
     *
     * @return ?string
     *  *   If null is returned, this feature is disabled.
     */
    public static function getCurrentVersionColumn(): ?string
    {
        return null;
    }

    /**
     * Determine whether model versioning is supported.
     *
     * @return bool
     */
    public static function isVersioningSupported(): bool
    {
        return static::getCurrentVersionColumn() !== null;
    }

    public function snapshots(): MorphMany
    {
        return $this->morphMany(...$this->getPolymorphicRelationArguments());
    }

    public function latestSnapshot(): MorphOne
    {
        $arguments = $this->getPolymorphicRelationArguments();

        return $this->morphOne(...$arguments)->latest($arguments['related']::getVersionColumn());
    }

    public function oldestSnapshot(): MorphOne
    {
        $arguments = $this->getPolymorphicRelationArguments();

        return $this->morphOne(...$arguments)->oldest($arguments['related']::getVersionColumn());
    }

    /**
     * Create a Model Snapshot.
     *
     * @param  array<string, mixed>|array<string, VirtualAttribute> $extraAttributes
     *
     * @return (SnapshotContract&Model)|null|false
     *  *   If successful, Snapshot instance gets returned.
     *  *   If the snapshotting Eloquent event gets cancelled, null gets returned.
     *  *   If another process has already acquired the lock, false gets returned.
     */
    public function takeSnapshot(array $extraAttributes = []): (SnapshotContract&Model)|null|false
    {
        $lockName = $this->suffixLockName(config('laravel-snap.concurrency.snapshotting-lock.name'));
        $lockTimeout = config('laravel-snap.concurrency.snapshotting-lock.timeout');

        /** @var (SnapshotContract&Model)|null|false */
        return Cache::lock($lockName, $lockTimeout)->get(function () use ($extraAttributes) {
            if ($this->fireModelEvent('snapshotting') === false) {
                return null;
            }

            /* @var AttributeCollectorInterface $collector */
            $collector = app(AttributeCollectorInterface::class);
            $snapshotAttributes = $collector->collectAttributes($this, static::getSnapshotDefinition(), $extraAttributes);

            $snapshot = $this->getConnection()->transaction(function () use ($snapshotAttributes) {
                $relation = $this->snapshots();
                $snapshotVersion = ($this->latestSnapshot?->getVersion() ?? 0) + 1;

                /** @var SnapshotContract&Model $snapshot */
                $snapshot = $relation->create([
                    ...$snapshotAttributes,
                    ...[
                        $relation->getRelated()::getVersionColumn() => $snapshotVersion
                    ]
                ]);

                if (static::isVersioningSupported()) {
                    $this->setCurrentVersion($snapshotVersion);
                }

                // Re-eager load the latestSnapshot so it gets set to the newly created Snapshot on subsequent calls.
                $this->load('latestSnapshot');

                return $snapshot;
            });

            $this->fireModelEvent('snapshot');

            return $snapshot;
        });
    }

    /**
     * Rewind the Model's state by a specific number of steps.
     *
     * @param  int $steps
     * @param  bool $shouldDefaultToNearest
     * @param  bool $shouldRestoreRelatedAttributes
     *
     * @return static|null|false
     *  *   If successful, the current Model instance with updated state gets returned.
     *  *   If the reverting Eloquent event gets cancelled, null gets returned.
     *  *   If another process has already acquired the lock, false gets returned.
     *
     * @throws SnapshotableModelException
     */
    public function rewind(int $steps = 1, bool $shouldDefaultToNearest = false, bool $shouldRestoreRelatedAttributes = true): static|null|false
    {
       $snapshot = $this->findSnapshotBySteps(-$steps, $shouldDefaultToNearest);

        return $this->revertTo($snapshot, $shouldRestoreRelatedAttributes);
    }

    /**
     * Forward the Model's state by a specific number of steps.
     *
     * @param  int $steps
     * @param  bool $shouldDefaultToNearest
     * @param  bool $shouldRestoreRelatedAttributes
     *
     * @return static|null|false
     *  *   If successful, the current Model instance with updated state gets returned.
     *  *   If the reverting Eloquent event gets cancelled, null gets returned.
     *  *   If another process has already acquired the lock, false gets returned.
     *
     * @throws SnapshotableModelException
     */
    public function forward(int $steps = 1, bool $shouldDefaultToNearest = false, bool $shouldRestoreRelatedAttributes = true): static|null|false
    {
        $snapshot = $this->findSnapshotBySteps($steps, $shouldDefaultToNearest);

        return $this->revertTo($snapshot, $shouldRestoreRelatedAttributes);
    }

    /**
     * Find nearest Snapshot by a number of steps relative to the Model's current version.
     *
     * @param  int  $steps
     * @param  bool $shouldDefaultToNearest
     *
     * @return Model&SnapshotContract
     *
     * @throws SnapshotableModelException
     */
    public function findSnapshotBySteps(int $steps, bool $shouldDefaultToNearest = false): SnapshotContract&Model
    {
        if (! static::isVersioningSupported()) {
            throw SnapshotableModelException::missingCurrentVersionColumn(static::class);
        }

        if ($steps === 0 ) {
            throw SnapshotableModelException::invalidNumberOfSteps($steps, static::class);
        }

        $relation = $this->snapshots();
        /* @var SnapshotContract&Model $related */
        $related = $relation->getRelated();

        $currentVersionColumn = static::getCurrentVersionColumn();
        $snapshotVersionColumn = $related::getVersionColumn();

        $targetVersion = $this->getAttribute($currentVersionColumn) + $steps;

        /* @var (SnapshotContract&Model)|null $snapshot */
        $snapshot = $relation
            ->when(
                $shouldDefaultToNearest,
                fn (Builder $query) => $query
                    ->where($snapshotVersionColumn, $steps > 0 ? ">=" : '<=', $targetVersion)
                    ->orderBy($snapshotVersionColumn, $steps > 0 ? 'asc' : 'desc'),
                fn (Builder $query) => $query
                    ->where($snapshotVersionColumn, $targetVersion)
            )
            ->first();

        if ($snapshot === null) {
            throw InvalidSnapshotException::snapshotNotFound(static::class);
        }

        return $snapshot;
    }

    /**
     * Revert the Model's state to a previously taken Snapshot.
     *
     * @param  SnapshotContract&Model $snapshot
     * @param  bool $shouldRestoreRelatedAttributes
     *
     * @return static|null|false
     *  *   If successful, the current Model instance with updated state gets returned.
     *  *   If the reverting Eloquent event gets cancelled, null gets returned.
     *  *   If another process has already acquired the lock, false gets returned.
     *
     * @throws InvalidSnapshotException
     */
    public function revertTo(SnapshotContract&Model $snapshot, bool $shouldRestoreRelatedAttributes = true): static|null|false
    {
        SnapshotValidator::assertIsRelatedToModel($snapshot, $this);

        $lockName = $this->suffixLockName(config('laravel-snap.concurrency.reverting-lock.name'));
        $lockTimeout = config('laravel-snap.concurrency.reverting-lock.timeout');

        /** @var static|null|false */
        return Cache::lock($lockName, $lockTimeout)->get(function () use ($snapshot, $shouldRestoreRelatedAttributes) {
            if ($this->fireModelEvent('reverting') === false) {
                return null;
            }

            /** @var AttributeRestorerInterface $restorer */
            $restorer = app(AttributeRestorerInterface::class);

            $model = $this->getConnection()->transaction(function () use ($restorer, $snapshot, $shouldRestoreRelatedAttributes) {
                if (static::isVersioningSupported()) {
                    $this->setCurrentVersion($snapshot->getVersion());
                }

                return $restorer->restoreFromSnapshot($this, $snapshot, $shouldRestoreRelatedAttributes);
            });

            $this->fireModelEvent('reverted');

            return $model;
        });
    }

    protected function initializeSnapshotable(): void
    {
        $this->observables = [
            ...$this->observables,
            ...[
                'reverting',
                'reverted',
                'snapshotting',
                'snapshot'
            ]
        ];
    }

    /**
     * Register a custom reverting model event with the dispatcher.
     *
     * @param  QueuedClosure|Closure|string|array  $callback
     *
     * @return void
     */
    public static function reverting(QueuedClosure|Closure|string|array $callback): void
    {
        static::registerModelEvent('reverting', $callback);
    }

    /**
     * Register a custom reverted model event with the dispatcher.
     *
     * @param  QueuedClosure|Closure|string|array  $callback
     *
     * @return void
     */
    public static function reverted(QueuedClosure|Closure|string|array $callback): void
    {
        static::registerModelEvent('reverted', $callback);
    }

    /**
     * Register a custom snapshotting model event with the dispatcher.
     *
     * @param  QueuedClosure|Closure|string|array  $callback
     *
     * @return void
     */
    public static function snapshotting(QueuedClosure|Closure|string|array $callback): void
    {
        static::registerModelEvent('snapshotting', $callback);
    }

    /**
     * Register a custom snapshot model event with the dispatcher.
     *
     * @param  QueuedClosure|Closure|string|array  $callback
     *
     * @return void
     */
    public static function snapshot(QueuedClosure|Closure|string|array $callback): void
    {
        static::registerModelEvent('snapshot', $callback);
    }

    /**
     * Return all arguments for polymorphic relation.
     *
     * @return array<string, mixed>
     */
    protected function getPolymorphicRelationArguments(): array
    {
        return [
            'related' => LaravelSnapServiceProvider::determineSnapshotModel(),
            'name' => config('laravel-snap.snapshot-model.morph_name'),
            'type' => config('laravel-snap.snapshot-model.morph-type'),
            'id' => config('laravel-snap.snapshot-model.morph-id'),
            'localKey' => config('laravel-snap.snapshot-model.local_key')
        ];
    }

    /**
     * The key has to be suffixed with table and key because Snapshotable trait
     * can be applied to multiple different models.
     *
     * @param  string $lockName
     *
     * @return string
     */
    protected function suffixLockName(string $lockName): string
    {
        return $lockName . "_" . $this->getTable() . "_" . $this->getKey();
    }

    /**
     * @param  int $version
     *
     * @return void
     */
    protected function setCurrentVersion(int $version): void
    {
        $this->update([
            static::getCurrentVersionColumn() => $version
        ]);
    }
}