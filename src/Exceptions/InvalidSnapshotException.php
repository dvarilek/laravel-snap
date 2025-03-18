<?php

declare(strict_types=1);

namespace Dvarilek\CompleteModelSnapshot\Exceptions;

use Dvarilek\CompleteModelSnapshot\Models\Contracts\SnapshotContract;
use Illuminate\Database\Eloquent\Model;

final class InvalidSnapshotException extends \InvalidArgumentException
{
    public static function invalidSnapshotMorph(
        SnapshotContract&Model $snapshot,
        Model $originModelCandidate,
        string $snapshotMorphTypeColumn,
        string $snapshotMorphKeyColumn
    ): \InvalidArgumentException
    {
        return new self(sprintf(
            "The provided snapshot '%s' is not associated with model '%s' (ID: %s). Expected morph type '%s' and morph ID '%s', but found morph type '%s' and morph ID '%s'.",
            $snapshot::class,
            $originModelCandidate::class,
            $originModelCandidate->getKey(),
            $originModelCandidate::class,
            $originModelCandidate->getKey(),
            $snapshotMorphTypeColumn,
            $snapshotMorphKeyColumn
        ));
    }
}