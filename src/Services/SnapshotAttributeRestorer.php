<?php

declare(strict_types=1);

namespace Dvarilek\LaravelSnap\Services;

use Dvarilek\LaravelSnap\DTO\AttributeTransferObject;
use Dvarilek\LaravelSnap\DTO\RelatedAttributeTransferObject;
use Dvarilek\LaravelSnap\Helpers\TransferObjectHelper;
use Dvarilek\LaravelSnap\Models\Contracts\SnapshotContract;
use Dvarilek\LaravelSnap\Services\Contracts\AttributeRestorerInterface;
use Illuminate\Database\Eloquent\Model;

class SnapshotAttributeRestorer implements AttributeRestorerInterface
{

    /**
     * @inheritDoc
     */
    public function rewindTo(Model $model, SnapshotContract&Model $snapshot, bool $shouldRestoreRelatedAttributes = true): Model
    {
        [$modelAttributes, $relatedAttributes] = static::divideSnapshotAttributes($snapshot);

        $this->restoreModelAttributes($model, $modelAttributes);

        if ($shouldRestoreRelatedAttributes) {
            $this->restoreRelatedAttributes($model, $relatedAttributes);
        }

        return $model;
    }

    /**
     * @inheritDoc
     */
    public function restoreModelAttributes(Model $model, array $modelSnapshotAttributes): void
    {
        /** @phpstan-ignore argument.type */
        $attributes = TransferObjectHelper::convertTransferObjectAttributesToModelAttributes($model, $modelSnapshotAttributes);

        $model->update($attributes);
    }

    /**
     * @inheritDoc
     */
    public function restoreRelatedAttributes(Model $model, array $relatedSnapshotAttributes): void
    {
        $relatedAttributeTransferObjectGroups = [];

        // Group the transfer objects by relation path so no redundant and repetitive queries get executed.
        foreach ($relatedSnapshotAttributes as $transferObject) {
            $relationPath = implode("->", $transferObject->relationPath);

            $relatedAttributeTransferObjectGroups[$relationPath][$transferObject->attribute] = $transferObject;
        }

        foreach ($relatedAttributeTransferObjectGroups as $relationPath => $transferObjectGroup) {
            $relatedModel = $model;

            foreach (explode("->", $relationPath) as $relationPathPart) {
                $relatedModel = $relatedModel->$relationPathPart;
            }

            if (! $relatedModel) {
                continue;
            }

            $this->restoreModelAttributes($relatedModel, $transferObjectGroup);
        }
    }

    /**
     * @param  SnapshotContract&Model $snapshot
     *
     * @return array{0: array<string, AttributeTransferObject>, 1: array<string, RelatedAttributeTransferObject>}
     */
    protected static function divideSnapshotAttributes(SnapshotContract&Model $snapshot): array
    {
        $snapshotAttributeFormats = $snapshot->getRawAttributes();

        /** @var array<string, mixed> $snapshotAttributes */
        $snapshotAttributes = $snapshot->toArray();
        $modelAttributes = $relatedAttributes = [];

        foreach ($snapshotAttributes as $key => $value) {
            $snapshotAttributeFormat = $snapshotAttributeFormats[$key] ?? null;

            if (TransferObjectHelper::isAttributeTransferObjectFormat($snapshotAttributeFormat)) {
                $modelAttributes[$key] = new AttributeTransferObject(
                    attribute: $snapshotAttributeFormat['attribute'],
                    value: $value,
                    cast: $snapshotAttributeFormat['cast'],
                );
            } elseif (TransferObjectHelper::isRelationTransferObjectFormat($snapshotAttributeFormat)) {
                $relatedAttributes[$key] = new RelatedAttributeTransferObject(
                    attribute: $snapshotAttributeFormat['attribute'],
                    value: $value,
                    cast: $snapshotAttributeFormat['cast'],
                    relationPath: $snapshotAttributeFormat['relationPath'],
                );
            }
        }

        return [$modelAttributes, $relatedAttributes];
    }
}