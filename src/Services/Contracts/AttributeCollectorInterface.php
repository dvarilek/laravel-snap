<?php

declare(strict_types=1);

namespace Dvarilek\CompleteModelSnapshot\Services\Contracts;

use Dvarilek\CompleteModelSnapshot\DTO\Contracts\VirtualAttribute;
use Dvarilek\CompleteModelSnapshot\DTO\RelatedAttributeTransferObject;
use Dvarilek\CompleteModelSnapshot\Services\SnapshotAttributeCollector;
use Dvarilek\CompleteModelSnapshot\ValueObjects\SnapshotDefinition;
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
     * @param  array<string, mixed>|array<string, VirtualAttribute> $extraAttributes
     *
     * @return array<string, VirtualAttribute>
     */
    public function collectAttributes(Model $model, SnapshotDefinition $definition, array $extraAttributes = []): array;


    /**
     * Get attributes from model.
     *
     * @param  Model $model
     * @param  SnapshotDefinition $definition
     *
     * @return array<string, mixed>
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