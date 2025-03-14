<?php

declare(strict_types=1);

namespace Dvarilek\CompleteModelSnapshot\Services\Contracts;

use Dvarilek\CompleteModelSnapshot\DTO\Contracts\VirtualAttribute;
use Dvarilek\CompleteModelSnapshot\DTO\RelatedAttributeTransferObject;
use Dvarilek\CompleteModelSnapshot\Models\Contracts\SnapshotContract;
use Dvarilek\CompleteModelSnapshot\Services\SnapshotAttributeRestorer;
use Illuminate\Database\Eloquent\Model;

/**
 * @see SnapshotAttributeRestorer Default implementation
 */
interface AttributeRestorerInterface
{

    /**
     * Rewind the model to a snapshot instance.
     *
     * @param  Model $model
     * @param  SnapshotContract&Model $snapshot
     * @param  bool $shouldRestoreRelatedAttributes
     *
     * @return Model
     */
    public function rewindTo(Model $model, SnapshotContract&Model $snapshot, bool $shouldRestoreRelatedAttributes = false): Model;

    /**
     * Restore attributes of a specific model.
     *
     * @param  Model $model
     * @param  array<string, VirtualAttribute> $modelSnapshotAttributes
     *
     * @return void
     */
    public function restoreModelAttributes(Model $model, array $modelSnapshotAttributes): void;

    /**
     * Restore related model attributes.
     *
     * @param  Model $model
     * @param  array<string, RelatedAttributeTransferObject> $relatedSnapshotAttributes
     *
     * @return void
     */
    public function restoreRelatedAttributes(Model $model, array $relatedSnapshotAttributes): void;
}