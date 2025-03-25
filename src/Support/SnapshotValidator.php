<?php

declare(strict_types=1);

namespace Dvarilek\LaravelSnap\Support;

use Dvarilek\LaravelSnap\Exceptions\InvalidSnapshotException;
use Dvarilek\LaravelSnap\Models\Contracts\SnapshotContract;
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
        $morphTypeColumn = config('laravel-snap.snapshot-model.morph-type');
        $morphKeyColumn = config('laravel-snap.snapshot-model.morph-id');

        if (
            $snapshot->$morphTypeColumn !== $originModelCandidate::class ||
            $snapshot->$morphKeyColumn !== $originModelCandidate->getKey()
        ) {
            throw InvalidSnapshotException::invalidSnapshotMorph($snapshot, $originModelCandidate, $morphTypeColumn, $morphKeyColumn);
        }
    }
}