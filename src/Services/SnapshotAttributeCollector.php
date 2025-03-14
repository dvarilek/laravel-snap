<?php

declare(strict_types=1);

namespace Dvarilek\CompleteModelSnapshot\Services;

use Dvarilek\CompleteModelSnapshot\DTO\Contracts\VirtualAttribute;
use Dvarilek\CompleteModelSnapshot\DTO\{AttributeTransferObject, RelatedAttributeTransferObject};
use Dvarilek\CompleteModelSnapshot\Helpers\ModelHelper;
use Dvarilek\CompleteModelSnapshot\Helpers\TransferObjectHelper;
use Dvarilek\CompleteModelSnapshot\Services\Contracts\AttributeCollectorInterface;
use Dvarilek\CompleteModelSnapshot\Support\RelationValidator;
use Dvarilek\CompleteModelSnapshot\ValueObjects\{EloquentSnapshotDefinition, SnapshotDefinition, RelationDefinition};
use Illuminate\Database\Eloquent\Model;

class SnapshotAttributeCollector implements AttributeCollectorInterface
{
    /**
     * @inheritDoc
     */
    public function collectAttributes(Model $model, SnapshotDefinition $definition, array $extraAttributes = []): array
    {
        $modelAttributes = $this->prepareModelAttributesForSnapshot(
            $this->getModelAttributes($model, $definition), $model
        );

        $casts = $definition->shouldCaptureCasts() ? $model->getCasts() : [];

        return [
            ...$this->mapToAttributeTransferObjects($modelAttributes, $casts),
            ...$this->mapToAttributeTransferObjects($extraAttributes),
            /** @phpstan-ignore argument.type */ // TODO: refactor
            ...$this->getRelatedAttributes($model, $definition),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getModelAttributes(Model $model, EloquentSnapshotDefinition $definition): array
    {
        if ($definition->shouldCaptureHiddenAttributes()) {
            $hiddenAttributes = $model->getHidden();

            $model = (clone $model)->makeVisible($hiddenAttributes);
        }

        return $this->filterAttributes($model->attributesToArray(), $model, $definition);
    }

    /**
     * Filter attributes based on definition rules
     *
     * @param  array<string, mixed> $attributes
     * @param  Model $model
     * @param  EloquentSnapshotDefinition $definition
     *
     * @return array<string, mixed>
     */
    protected function filterAttributes(array $attributes, Model $model, EloquentSnapshotDefinition $definition): array
    {
        if (!$definition->shouldCaptureAllAttributes()) {
            $attributes = array_intersect_key(
                $attributes,
                array_flip($definition->getCapturedAttributes())
            );
        }

        $excludedAttributes = $definition->getExcludedAttributes();

        if ($definition->shouldExcludeTimestamps()) {
            $excludedAttributes = [
                ...$excludedAttributes,
                ...ModelHelper::getTimestampAttributes($model),
            ];
        }

        return array_diff_key(
            $attributes,
            array_flip($excludedAttributes)
        );
    }

    /**
     * @inheritDoc
     */
    public function getRelatedAttributes(Model $model, EloquentSnapshotDefinition $definition): array
    {
        // TODO: Find a way to handle the collection of attributes inside this method

        return $this->collectRelatedAttributes($model, $definition->getRelations());
    }

    /**
     * @param  Model $model
     * @param  list<RelationDefinition> $relationDefinitions
     * @param  list<string> $basePath
     *
     * @return array<string, RelatedAttributeTransferObject>
     */
    private function collectRelatedAttributes(Model $model, array $relationDefinitions, array $basePath = []): array
    {
        $collectedAttributes = [];

        foreach ($relationDefinitions as $relationDefinition) {
            RelationValidator::assertValid($model, $relationDefinition->getName());

            $relationName = $relationDefinition->getName();
            $relatedModel = $model->$relationName;

            // Relation is valid, but no model exists.
            if (is_null($relatedModel)) {
                continue;
            }

            $currentPath = [...$basePath, $relationName];
            $casts = $relationDefinition->shouldCaptureCasts() ? $relatedModel->getCasts() : [];

            // Always append the key so related records can be easily identified.
            $attributes = $this->getModelAttributes($relatedModel, $relationDefinition);
            $attributes[$relatedModel->getKeyName()] = $relatedModel->getKey();

            foreach ($attributes as $attribute => $value) {
                $transferObject = new RelatedAttributeTransferObject(
                    attribute: $attribute,
                    value: $value,
                    cast: $casts[$attribute] ?? null,
                    relationPath: $currentPath
                );

                $collectedAttributes[TransferObjectHelper::createQualifiedRelationName($transferObject)] = $transferObject;
            }

            $nestedRelationDefinitions = $relationDefinition->getRelations();

            // Recursively collect nested related attributes
            if (count($nestedRelationDefinitions) > 0) {
                $collectedAttributes += $this->collectRelatedAttributes($relatedModel, $nestedRelationDefinitions, $currentPath);
            }
        }

        return $collectedAttributes;
    }

    /**
     * @param  array<string, mixed> $attributes
     * @param  Model $model
     * @return array<string, mixed>
     */
    protected function prepareModelAttributesForSnapshot(array $attributes, Model $model): array
    {
        // The snapshot will already maintain a bond with the original model through a polymorphic relation.
        // Having a key present is redundant and could potentially result in naming conflicts on the snapshot model.
        unset($attributes[$model->getKeyName()]);

        $timestampAttributes = ModelHelper::getTimestampAttributes($model);
        $prefix = config('complete-model-snapshot.timestamp-prefix');

        foreach ($attributes as $key => $value) {
            // The prefix has to be added to prevent naming conflicts on the snapshot model.
            if (in_array($key, $timestampAttributes)) {
                $attributes[$prefix . $key] = $value;
                unset($attributes[$key]);
            }
        }

        return $attributes;
    }

    /**
     * @param  array<string, mixed>|array<string, VirtualAttribute> $attributes
     * @param  array<string, mixed> $casts
     *
     * @return array<string, VirtualAttribute>
     */
    public function mapToAttributeTransferObjects(array $attributes, array $casts = []): array
    {
        $transferObjects = [];

        foreach ($attributes as $key => $value) {
            if ($value instanceof VirtualAttribute) {
                $transferObjects[$key] = $value;
                continue;
            }

            $transferObjects[$key] = new AttributeTransferObject(
                attribute: $key,
                value: $value,
                cast: $casts[$key] ?? null,
            );
        }

        return $transferObjects;
    }
}