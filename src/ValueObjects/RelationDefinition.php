<?php

namespace Dvarilek\LaravelSnapshotTree\ValueObjects;

class RelationDefinition extends SnapshotDefinition
{

    public function __construct(
        protected ?string $name = null
    ) {}

    public static function from(string $relationName): static
    {
        return new static($relationName);
    }

    public function name(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}