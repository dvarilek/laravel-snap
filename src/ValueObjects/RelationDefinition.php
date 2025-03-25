<?php

declare(strict_types=1);

namespace Dvarilek\LaravelSnap\ValueObjects;

class RelationDefinition extends EloquentSnapshotDefinition
{

    public function __construct(
        protected string $relationName
    ) {}

    public static function from(string $relationName): static
    {
        /** @phpstan-ignore new.static */
        return new static($relationName);
    }

    public function name(string $relationName): static
    {
        $this->relationName = $relationName;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->relationName;
    }
}