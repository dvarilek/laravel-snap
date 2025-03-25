<?php

declare(strict_types=1);

namespace Dvarilek\LaravelSnap\Services\Contracts;

use Dvarilek\LaravelSnap\DTO\Contracts\VirtualAttribute;
use Dvarilek\LaravelSnap\DTO\RelatedAttributeTransferObject;
use Dvarilek\LaravelSnap\Services\SnapshotAttributeCollector;
use Dvarilek\LaravelSnap\ValueObjects\EloquentSnapshotDefinition;
use Dvarilek\LaravelSnap\ValueObjects\RelationDefinition;
use Dvarilek\LaravelSnap\ValueObjects\SnapshotDefinition;
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
     * @param  EloquentSnapshotDefinition $definition
     *
     * @return array<string, mixed>
     */
    public function getModelAttributes(Model $model, EloquentSnapshotDefinition $definition): array;

    /**
     * Get attributes from related models.
     *
     * @param  Model $model
     * @param  RelationDefinition $definition
     *
     * @return array<string, RelatedAttributeTransferObject>
     */
    public function getRelatedAttributes(Model $model, RelationDefinition $definition): array;
}