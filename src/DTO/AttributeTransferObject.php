<?php

namespace Dvarilek\LaravelSnapshotTree\DTO;

use Illuminate\Contracts\Support\Arrayable;

final readonly class AttributeTransferObject implements \JsonSerializable, Arrayable
{
    public function __construct(
        public string $attribute,
        public ?string $value,
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