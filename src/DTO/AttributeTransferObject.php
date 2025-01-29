<?php

declare(strict_types=1);

namespace Dvarilek\LaravelSnapshotTree\DTO;

use Dvarilek\LaravelSnapshotTree\DTO\Contracts\VirtualAttribute;

final readonly class AttributeTransferObject implements VirtualAttribute
{
    public function __construct(
        public string $attribute,
        public mixed $value,
        public ?string $cast
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
        ];
    }
}