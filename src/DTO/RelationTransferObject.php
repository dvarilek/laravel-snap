<?php

namespace Dvarilek\LaravelSnapshotTree\DTO;

use Illuminate\Contracts\Support\Arrayable;

/**
 * A data transfer object for passing information about a relation.
 */
final readonly class RelationTransferObject implements \JsonSerializable, Arrayable
{
    public function __construct(
        public string $attribute,
        public ?string $value,
        public string $relationPath,
    ) {}

    /**
     * Return the qualified relation name - attribute prefixed with relation path.
     *
     * @return string
     */
    public function getQualifiedRelationName(): string
    {
        return $this->relationPath . '_' . $this->attribute;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return [
            'attribute' => $this->attribute,
            'value' => $this->value,
            'relationPath' => $this->relationPath,
        ];
    }
}