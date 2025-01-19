<?php

namespace Dvarilek\LaravelSnapshotTree\Models\Concerns;

use Dvarilek\LaravelSnapshotTree\Models\Snapshot;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * @mixin Model
 */
trait Snapshotable
{

    // TODO: Implement

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