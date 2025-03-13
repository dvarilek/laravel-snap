<?php

declare(strict_types=1);

namespace Dvarilek\CompleteModelSnapshot\Exceptions;

use Illuminate\Database\Eloquent\Model;

final class InvalidConfigurationException extends \Exception
{
    /**
     * @param  class-string $class
     * @return self
     */
    public static function modelMustBeSubtypeOfModel(string $class): self
    {
        return new self("Snapshot model must be a subclass of " . Model::class . " " . $class . ", given.");
    }

    /**
     * @return self
     */
    public static function modelMustImplementSnapshotContractInterface(): self
    {
        return new self("Snapshot model must implement the SnapshotContract interface");
    }
}