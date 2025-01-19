<?php

namespace Dvarilek\LaravelSnapshotTree\Models\Concerns;

use Dvarilek\LaravelSnapshotTree\Models\Snapshot;
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
            'origin_type',
            'origin_id',
            'id',
        );
    }

}