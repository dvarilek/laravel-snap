<?php

namespace Dvarilek\LaravelSnapshotTree\Models\Concerns;

use Dvarilek\LaravelSnapshotTree\DTO\Contracts\VirtualAttributeInterface;
use Dvarilek\LaravelSnapshotTree\Models\Snapshot;
use Dvarilek\LaravelSnapshotTree\Services\Contracts\AttributeCollectorInterface;
use Dvarilek\LaravelSnapshotTree\ValueObjects\SnapshotDefinition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * @mixin Model
 */
trait Snapshotable
{

    /**
     * Configure what should and shouldn't be captured in a snapshot.
     *
     * @return SnapshotDefinition
     */
    abstract public static function getSnapshotDefinition(): SnapshotDefinition;

    public function snapshot(): MorphMany
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

    /**
     * Return all arguments for polymorphic relation.
     *
     * @return array<string, mixed>
     */
    protected function getPolymorphicRelationArguments(): array
    {
        return [
            'related' => config('snapshot-tree.snapshot-model.model'),
            'name' => config('snapshot-tree.snapshot-model.morph_name'),
            'type' => config('snapshot-tree.snapshot-model.morph-type'),
            'id' => config('snapshot-tree.snapshot-model.morph-id'),
            'localKey' => config('snapshot-tree.snapshot-model.local_key')
        ];
    }
}