<?php

declare(strict_types=1);

namespace Dvarilek\CompleteModelSnapshot\ValueObjects;

class SnapshotDefinition extends EloquentSnapshotDefinition
{

    public static function make(): static
    {
        return new static();
    }
}