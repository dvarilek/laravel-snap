<?php

declare(strict_types=1);

namespace Dvarilek\LaravelSnap\Exceptions;

use Dvarilek\LaravelSnap\DTO\AttributeTransferObject;
use Dvarilek\LaravelSnap\DTO\RelatedAttributeTransferObject;
use Dvarilek\LaravelSnap\Models\Contracts\SnapshotContract;
use Illuminate\Database\Eloquent\Model;

final class InvalidSnapshotException extends \InvalidArgumentException
{
    /**
     * @param  Model&SnapshotContract $snapshot
     * @param  Model $originModelCandidate
     * @param  string $snapshotMorphTypeColumn
     * @param  string $snapshotMorphKeyColumn
     *
     * @return self
     */
    public static function invalidSnapshotMorph(
        SnapshotContract&Model $snapshot,
        Model $originModelCandidate,
        string $snapshotMorphTypeColumn,
        string $snapshotMorphKeyColumn
    ): self
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

    /**
     * @param  string $attribute
     * @param  mixed $value
     *
     * @return self
     */
    public static function invalidSnapshotAttributeStructure(string $attribute, mixed $value): self
    {
        return new self(sprintf(
            "The Snapshot attribute '%s' has an incorrectly structured value %s, the structure must conform to either %s or %s.",
            $attribute,
            $value,
            AttributeTransferObject::class,
            RelatedAttributeTransferObject::class
        ));
    }
}