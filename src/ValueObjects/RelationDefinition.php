<?php

namespace Dvarilek\LaravelSnapshotTree\ValueObjects;

class RelationDefinition extends SnapshotDefinition
{

    public function __construct(
        protected ?string $name = null
    ) {}

    public static function make(?string $name = null): static
    {
        return new static($name);
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