<?php

namespace Dvarilek\LaravelSnapshotTree\Services\Contracts;

use Dvarilek\LaravelSnapshotTree\DTO\AttributeTransferObject;
use Dvarilek\LaravelSnapshotTree\DTO\Contracts\VirtualAttributeInterface;
use Dvarilek\LaravelSnapshotTree\DTO\RelatedAttributeTransferObject;
use Dvarilek\LaravelSnapshotTree\Services\SnapshotAttributeCollector;
use Dvarilek\LaravelSnapshotTree\ValueObjects\SnapshotDefinition;
use Illuminate\Database\Eloquent\Model;

/**
 * @see SnapshotAttributeCollector Default implementation
 */
interface AttributeCollectorInterface
{

    /**
     * Collect all attributes from model and its related models.
     *
     * @param  Model $model
     * @param  SnapshotDefinition $definition
     * @param  array<string, mixed>|array<string, VirtualAttributeInterface> $extraAttributes
     *
     * @return array<string, VirtualAttributeInterface>
     */
    public function collectAttributes(Model $model, SnapshotDefinition $definition, array $extraAttributes = []): array;


    /**
     * Get attributes from model.
     *
     * @param  Model $model
     * @param  SnapshotDefinition $definition
     *
     * @return array<string, AttributeTransferObject>
     */
    public function getModelAttributes(Model $model, SnapshotDefinition $definition): array;

    /**
     * Get attributes from related models.
     *
     * @param  Model $model
     * @param  SnapshotDefinition $definition
     *
     * @return array<string, RelatedAttributeTransferObject>
     */
    public function getRelatedAttributes(Model $model, SnapshotDefinition $definition): array;
}