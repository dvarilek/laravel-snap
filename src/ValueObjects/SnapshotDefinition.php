<?php

declare(strict_types=1);

namespace Dvarilek\LaravelSnap\ValueObjects;

class SnapshotDefinition extends EloquentSnapshotDefinition
{

    public static function make(): static
    {
        /** @phpstan-ignore new.static */
        return new static();
    }
}