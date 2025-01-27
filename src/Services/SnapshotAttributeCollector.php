<?php

namespace Dvarilek\LaravelSnapshotTree\Services;

use Dvarilek\LaravelSnapshotTree\DTO\Contracts\VirtualAttributeInterface;
use Dvarilek\LaravelSnapshotTree\DTO\{AttributeTransferObject, RelatedAttributeTransferObject};
use Dvarilek\LaravelSnapshotTree\Helpers\ModelHelper;
use Dvarilek\LaravelSnapshotTree\Helpers\TransferObjectHelper;
use Dvarilek\LaravelSnapshotTree\Services\Contracts\AttributeCollectorInterface;
use Dvarilek\LaravelSnapshotTree\Support\RelationValidator;
use Dvarilek\LaravelSnapshotTree\ValueObjects\{SnapshotDefinition, RelationDefinition};
use Illuminate\Database\Eloquent\Model;

class SnapshotAttributeCollector implements AttributeCollectorInterface
{
    /**
     * @inheritDoc
     */
    public function collectAttributes(Model $model, SnapshotDefinition $definition, array $extraAttributes = []): array
    {
        $modelAttributes = $this->getModelAttributes($model, $definition);

        return [
            ...$this->mapToTransferObjects($modelAttributes, $model, $definition),
            ...$this->prepareExtraAttributes($extraAttributes),
            ...$this->getRelatedAttributes($model, $definition),
        ];
    }

    /**
     * @inheritDoc
     */
    public function getModelAttributes(Model $model, SnapshotDefinition $definition): array
    {
        if ($definition->shouldCaptureHiddenAttributes()) {
            $hiddenAttributes = $model->getHidden();

            $model = (clone $model)->makeVisible($hiddenAttributes);
        }

        // The snapshot already maintains a bond with the original model through a polymorphic relation.
        // This also prevents naming conflicts with snapshot's key name.
        unset($model[$model->getKeyName()]);

        return $this->filterAttributes($model->attributesToArray(), $model, $definition);
    }

    /**
     * Filter attributes based on definition rules
     *
     * @param  array<string, mixed> $attributes
     * @param  Model $model
     * @param  SnapshotDefinition $definition
     *
     * @return array<string, mixed>
     */
    protected function filterAttributes(array $attributes, Model $model, SnapshotDefinition $definition): array
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
    public function getRelatedAttributes(Model $model, SnapshotDefinition $definition): array
    {
        // TODO: Find a way to handle the collection of attributes inside this method

        return $this->collectRelatedAttributes($model, $definition->getRelations());
    }

    /**
     * @param  Model $model
     * @param  list<RelationDefinition> $relationDefinitions
     * @param  array $basePath
     *
     * @return array<string, RelatedAttributeTransferObject>
     */
    private function collectRelatedAttributes(Model $model, array $relationDefinitions, array $basePath = []): array
    {
        $collectedAttributes = [];

        foreach ($relationDefinitions as $relationDefinition) {
            RelationValidator::assertValid($model, $relationDefinition);

            $relationName = $relationDefinition->getName();
            $relatedModel = $model->$relationName;

            // Relation is valid, but no model exists.
            if (is_null($relatedModel)) {
                continue;
            }

            $currentPath = [...$basePath, $relationName];

            $attributes = $this->getModelAttributes($relatedModel, $relationDefinition);
            // Always append the primary key for related attributes so they can be identifier later.
            $attributes[$relatedModel->getKeyName()] = $relatedModel->getOriginal($relatedModel->getKeyName());

            foreach ($attributes as $attribute => $value) {
                $transferObject = new RelatedAttributeTransferObject(
                    attribute: $attribute,
                    value: $value,
                    cast: $this->getCast($attribute, $relatedModel, $relationDefinition),
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
     * @param  array<> $attributes
     * @param  Model $model
     * @param  SnapshotDefinition $definition
     *
     * @return string|null
     */
    protected function getCast(string $attribute, Model $model, SnapshotDefinition $definition): ?string
    {
        if (!$definition->shouldCaptureCasts()) {
            return null;
        }

        return $model->getCasts()[$attribute] ?? null;
    }

    /**
     * @param  array<string, mixed>|array<string, VirtualAttributeInterface> $extraAttributes
     *
     * @return array<string, mixed>|array<string, VirtualAttributeInterface>
     */
    public function prepareExtraAttributes(array $extraAttributes): array
    {
        $transferObjects = [];

        foreach ($extraAttributes as $attribute => $value) {
            if ($value instanceof VirtualAttributeInterface) {
                $transferObjects[$attribute] = $value;
            } else {
                $transferObjects[$attribute] = new AttributeTransferObject(
                    attribute: $attribute,
                    value: $value,
                    cast: null,
                );
            }
        }

        return $transferObjects;
    }

    /**
     * @param  array<string, mixed> $attributes
     * @param  SnapshotDefinition $definition
     * @param  Model $model
     *
     * @return array<string, AttributeTransferObject>
     */
    protected function mapToTransferObjects(array $attributes, Model $model, SnapshotDefinition $definition): array
    {
        $transferObjects = [];

        foreach ($attributes as $attribute => $value) {
            $transferObjects[$attribute] = new AttributeTransferObject(
                attribute: $attribute,
                value: $value,
                cast: $this->getCast($attribute, $model, $definition)
            );
        }

        return $transferObjects;
    }
}