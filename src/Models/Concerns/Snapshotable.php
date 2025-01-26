<?php

namespace Dvarilek\LaravelSnapshotTree\Models\Concerns;

use Dvarilek\LaravelSnapshotTree\DTO\Contracts\VirtualAttributeInterface;
use Dvarilek\LaravelSnapshotTree\Models\Snapshot;
use Dvarilek\LaravelSnapshotTree\Services\Contracts\AttributeCollectorInterface;
use Dvarilek\LaravelSnapshotTree\ValueObjects\SnapshotDefinition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin Model
 */
trait Snapshotable
{

    // TODO: Implement

    abstract public static function getSnapshotDefinition(): SnapshotDefinition;

    public function snapshot(): MorphMany
    {
        return $this->morphMany(
            Snapshot::class,
            'origin',
        );
    }

    /**
     * Create a model snapshot.
     *
     * @param  array<string, mixed>|array<string, VirtualAttributeInterface> $extraAttributes
     *
     * @return Snapshot
     */
    public function makeSnapshot(array $extraAttributes = []): Snapshot
    {
        $attributes = $this->collectSnapshotAttributes($extraAttributes);

        /** @var Snapshot */
        return $this->snapshot()->create($attributes);
    }

    /**
     * Collect the attributes that should be snapshot.
     *
     * @param  array<string, mixed>|array<string, VirtualAttributeInterface> $extraAttributes
     *
     * @return array<string, VirtualAttributeInterface>
     */
    public function collectSnapshotAttributes(array $extraAttributes = []): array
    {
        /** @var AttributeCollectorInterface $collector */
        $collector = app(AttributeCollectorInterface::class);

        return $collector->collectAttributes($this, static::getSnapshotDefinition(), $extraAttributes);
    }

}