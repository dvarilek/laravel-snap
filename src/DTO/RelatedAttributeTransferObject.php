<?php

namespace Dvarilek\LaravelSnapshotTree\DTO;

use Dvarilek\LaravelSnapshotTree\DTO\Contracts\VirtualAttributeInterface;

final readonly class RelatedAttributeTransferObject implements VirtualAttributeInterface
{
    public function __construct(
        public string $attribute,
        public mixed $value,
        public ?string $cast,
        public array $relationPath,
    ) {}

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toArray(): array
    {
        return [
            'attribute' => $this->attribute,
            'value' => $this->value,
            'cast' => $this->cast,
            'relationPath' => $this->relationPath,
        ];
    }
}