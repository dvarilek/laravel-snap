<?php

declare(strict_types=1);

namespace Dvarilek\CompleteModelSnapshot\Support;

use Dvarilek\CompleteModelSnapshot\Exceptions\InvalidSnapshotException;
use Dvarilek\CompleteModelSnapshot\Models\Contracts\SnapshotContract;
use Illuminate\Database\Eloquent\Model;

final class SnapshotValidator
{

    /**
     * @param  Model&SnapshotContract $snapshot
     * @param  Model $originModelCandidate
     *
     * @return void
     */
    public static function assertValid(SnapshotContract&Model $snapshot, Model $originModelCandidate): void
    {
        $morphTypeColumn = config('complete-model-snapshot.snapshot-model.morph-type');
        $morphKeyColumn = config('complete-model-snapshot.snapshot-model.morph-id');

        if (
            $snapshot->$morphTypeColumn !== $originModelCandidate::class ||
            $snapshot->$morphKeyColumn !== $originModelCandidate->getKey()
        ) {
            throw InvalidSnapshotException::invalidSnapshotMorph($snapshot, $originModelCandidate, $morphTypeColumn, $morphKeyColumn);
        }
    }
}