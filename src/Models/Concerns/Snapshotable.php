<?php

declare(strict_types=1);

namespace Dvarilek\LaravelSnap\Models\Concerns;

use Dvarilek\LaravelSnap\DTO\Contracts\VirtualAttribute;
use Dvarilek\LaravelSnap\Exceptions\InvalidSnapshotException;
use Dvarilek\LaravelSnap\LaravelSnapServiceProvider;
use Dvarilek\LaravelSnap\Models\Contracts\SnapshotContract;
use Dvarilek\LaravelSnap\Models\Snapshot;
use Dvarilek\LaravelSnap\Services\Contracts\AttributeCollectorInterface;
use Dvarilek\LaravelSnap\Services\Contracts\AttributeRestorerInterface;
use Dvarilek\LaravelSnap\Support\SnapshotValidator;
use Dvarilek\LaravelSnap\ValueObjects\SnapshotDefinition;
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

    public function snapshots(): MorphMany
    {
        return $this->morphMany(...$this->getPolymorphicRelationArguments());
    }

    public function latestSnapshot(): MorphOne
    {
        return $this->morphOne(...$this->getPolymorphicRelationArguments())->latest();
    }

    public function oldestSnapshot(): MorphOne
    {
        return $this->morphOne(...$this->getPolymorphicRelationArguments())->oldest();
    }

    /**
     * Create a model snapshot.
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

            $snapshotAttributes = $this->collectSnapshotAttributes($extraAttributes);

            $snapshot = $this->getConnection()->transaction(fn () => $this->snapshots()->create($snapshotAttributes));

            $this->fireModelEvent('snapshot');

            return $snapshot;
        });
    }

    /**
     * Rewind the model to a concrete snapshot instance.
     *
     * @param  Snapshot $snapshot
     * @param  bool $shouldRestoreRelatedAttributes
     *
     * @return static|null|false
     *  *   If successful, the current instance with updated state gets returned.
     *  *   If the rewinding Eloquent event gets cancelled, null gets returned.
     *  *   If another process has already acquired the lock, false gets returned.
     *
     * @throws InvalidSnapshotException
     */
    public function rewindTo(SnapshotContract&Model $snapshot, bool $shouldRestoreRelatedAttributes = true): static|null|false
    {
        SnapshotValidator::assertValid($snapshot, $this);

        $lockName = $this->suffixLockName(config('laravel-snap.concurrency.rewinding-lock.name'));
        $lockTimeout = config('laravel-snap.concurrency.rewinding-lock.timeout');

        /** @var static|null|false */
        return Cache::lock($lockName, $lockTimeout)->get(function () use ($snapshot, $shouldRestoreRelatedAttributes) {
            if ($this->fireModelEvent('rewinding') === false) {
                return null;
            }

            /** @var AttributeRestorerInterface $restorer */
            $restorer = app(AttributeRestorerInterface::class);

            $model = $this->getConnection()->transaction(fn () => $restorer->rewindTo($this, $snapshot, $shouldRestoreRelatedAttributes));

            $this->fireModelEvent('rewound');

            return $model;
        });
    }

    /**
     * Collect the attributes that should be snapshot.
     *
     * @param  array<string, mixed>|array<string, VirtualAttribute> $extraAttributes
     *84
     * @return array<string, VirtualAttribute>
     */
    public function collectSnapshotAttributes(array $extraAttributes = []): array
    {
        /** @var AttributeCollectorInterface $collector */
        $collector = app(AttributeCollectorInterface::class);

        return $collector->collectAttributes($this, static::getSnapshotDefinition(), $extraAttributes);
    }

    protected function initializeSnapshotable(): void
    {
        $this->observables = [
            ...$this->observables,
            ...[
                'rewinding',
                'rewound',
                'snapshotting',
                'snapshot'
            ]
        ];
    }

    /**
     * Register a custom rewinding model event with the dispatcher.
     *
     * @param  QueuedClosure|Closure|string|array  $callback
     *
     * @return void
     */
    public static function rewinding(QueuedClosure|Closure|string|array $callback): void
    {
        static::registerModelEvent('rewinding', $callback);
    }

    /**
     * Register a custom rewound model event with the dispatcher.
     *
     * @param  QueuedClosure|Closure|string|array  $callback
     *
     * @return void
     */
    public static function rewound(QueuedClosure|Closure|string|array $callback): void
    {
        static::registerModelEvent('rewound', $callback);
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
}