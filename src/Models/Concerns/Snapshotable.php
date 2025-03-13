<?php

declare(strict_types=1);

namespace Dvarilek\CompleteModelSnapshot\Models\Concerns;

use Dvarilek\CompleteModelSnapshot\DTO\Contracts\VirtualAttribute;
use Dvarilek\CompleteModelSnapshot\LaravelCompleteModelSnapshotServiceProvider;
use Dvarilek\CompleteModelSnapshot\Models\Contracts\SnapshotContract;
use Dvarilek\CompleteModelSnapshot\Models\Snapshot;
use Dvarilek\CompleteModelSnapshot\Services\Contracts\AttributeCollectorInterface;
use Dvarilek\CompleteModelSnapshot\ValueObjects\SnapshotDefinition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

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
     * @param  array<string, mixed>|array<string, VirtualAttribute> $extraAttributes
     *
     * @return SnapshotContract&Model
     */
    public function takeSnapshot(array $extraAttributes = []): SnapshotContract&Model
    {
        $attributes = $this->collectSnapshotAttributes($extraAttributes);

        /** @var SnapshotContract&Model */
        return $this->snapshot()->create($attributes);
    }

    /**
     * Collect the attributes that should be snapshot.
     *
     * @param  array<string, mixed>|array<string, VirtualAttribute> $extraAttributes
     *
     * @return array<string, VirtualAttribute>
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
            'related' => LaravelCompleteModelSnapshotServiceProvider::determineSnapshotModel(),
            'name' => config('complete-model-snapshot.snapshot-model.morph_name'),
            'type' => config('complete-model-snapshot.snapshot-model.morph-type'),
            'id' => config('complete-model-snapshot.snapshot-model.morph-id'),
            'localKey' => config('complete-model-snapshot.snapshot-model.local_key')
        ];
    }
}