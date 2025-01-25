<?php

namespace Dvarilek\LaravelSnapshotTree\Services;

use Dvarilek\LaravelSnapshotTree\DTO\Contracts\VirtualAttributeInterface;
use Dvarilek\LaravelSnapshotTree\DTO\{AttributeTransferObject, RelatedAttributeTransferObject};
use Dvarilek\LaravelSnapshotTree\Helpers\TransferObjectHelper;
use Dvarilek\LaravelSnapshotTree\Services\Contracts\AttributeCollectorInterface;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Support\Str;
use Dvarilek\LaravelSnapshotTree\ValueObjects\{SnapshotDefinition, RelationDefinition};
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class SnapshotAttributeCollector implements AttributeCollectorInterface
{
    /**
     * @inheritDoc
     */
    public function collectAttributes(Model $model, SnapshotDefinition $definition, array $extraAttributes = []): array
    {
        return [
            ...$this->getModelAttributes($model, $definition),
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

        $attributes = $this->filterAttributes($model->attributesToArray(), $definition);

        // Prefix primary key to prevent naming conflicts on Snapshot model.
        $this->prefixPrimaryKey($model, $definition, $attributes);

        return $this->mapToTransferObjects($attributes, $definition);
    }

    /**
     * Filter attributes based on definition rules
     *
     * @param  array<string, mixed> $attributes
     * @param  SnapshotDefinition $definition
     *
     * @return array<string, mixed>
     */
    protected function filterAttributes(array $attributes, SnapshotDefinition $definition): array
    {
        if (!$definition->shouldCaptureAllAttributes()) {
            $attributes = array_intersect_key(
                $attributes,
                array_flip($definition->getCapturedAttributes())
            );
        }

        return array_diff_key(
            $attributes,
            array_flip($definition->getExcludedAttributes())
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
        $attributes = [];

        foreach ($relationDefinitions as $relationDefinition) {
            $this->assertRelationIsValid($model, $relationDefinition);

            $relationName = $relationDefinition->getName();
            $relatedModel = $model->$relationName;

            if (is_null($relatedModel)) {
                continue;
            }

            $currentPath = [...$basePath, $relationName];

            foreach ($this->getModelAttributes($relatedModel, $relationDefinition) as $attribute => $value) {
                $transferObject = new RelatedAttributeTransferObject(
                    attribute: $attribute,
                    value: $value->value,
                    cast: null, // TODO: Add cast from definition
                    relationPath: $currentPath
                );

                $attributes[TransferObjectHelper::createQualifiedRelationName($transferObject)] = $transferObject;
            }

            $nestedRelationDefinitions = $relationDefinition->getRelations();

            // Recursively collect nested related attributes
            if (count($nestedRelationDefinitions) > 0) {
                $attributes += $this->collectRelatedAttributes($relatedModel, $nestedRelationDefinitions, $currentPath);
            }
        }

        return $attributes;
    }

    /**
     * Validate that a relation exists and is of an allowed type
     *
     * @param Model $model
     * @param RelationDefinition $definition
     */
    protected function assertRelationIsValid(Model $model, RelationDefinition $definition): void
    {
        // TODO: Consider adding package specific exceptions
        $relationName = $definition->getName();

        if (!$relationName) {
            throw new \InvalidArgumentException(sprintf('A relationship on model %s does not have a name defined in definition.',
                    $model::class
                )
            );
        }

        if (!method_exists($model, $relationName)) {
            throw new RelationNotFoundException(sprintf('The relationship %s does not exist on model %s.',
                    $relationName, $model::class
                )
            );
        }

        $relation = $model->$relationName();

        if (!$relation instanceof BelongsTo) {
            throw new \InvalidArgumentException(sprintf('The relationship %s on model %s must be of type %s, %s provided.',
                    $relationName, $model::class, BelongsTo::class, $relation::class
                )
            );
        }
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
     * @param  Model $model
     * @param  array<string, mixed> $attributes
     * @param  SnapshotDefinition $definition
     *
     * @return void
     */
    protected function prefixPrimaryKey(Model $model, SnapshotDefinition $definition, array &$attributes): void
    {
        $keyName = $model->getKeyName();

        foreach ($attributes as $attribute => $value) {
            if ($keyName === $attribute) {
                $prefix = $definition->getPrimaryKeyPrefix();

                $prefixedKey = $prefix
                    ? $prefix . $keyName
                    : Str::snake(class_basename($model::class)) . '_' . $keyName;

                $attributes[$prefixedKey] = $value;
                unset($attributes[$attribute]);

                return;
            }
        }
    }

    /**
     * @param  array<string, mixed> $attributes
     * @param  null|SnapshotDefinition $definition
     *
     * @return array<string, AttributeTransferObject>
     */
    protected function mapToTransferObjects(array $attributes, ?SnapshotDefinition $definition = null): array
    {
        $transferObjects = [];

        foreach ($attributes as $attribute => $value) {
            $transferObjects[$attribute] = new AttributeTransferObject(
                attribute: $attribute,
                value: $value,
                cast: null // TODO: add casts
            );
        }

        return $transferObjects;
    }
}