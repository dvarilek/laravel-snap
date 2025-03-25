<?php

declare(strict_types=1);

namespace Dvarilek\LaravelSnap\DTO;

use Dvarilek\LaravelSnap\DTO\Contracts\VirtualAttribute;

final readonly class AttributeTransferObject implements VirtualAttribute
{
    public function __construct(
        public string $attribute,
        public mixed $value,
        public ?string $cast
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'attribute' => $this->attribute,
            'value' => $this->value,
            'cast' => $this->cast,
        ];
    }
}