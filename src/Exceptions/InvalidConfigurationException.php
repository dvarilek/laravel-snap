<?php

declare(strict_types=1);

namespace Dvarilek\CompleteModelSnapshot\Exceptions;

use Dvarilek\CompleteModelSnapshot\Models\Contracts\SnapshotContract;
use Illuminate\Database\Eloquent\Model;

final class InvalidConfigurationException extends \Exception
{
    /**
     * @param  class-string<Model> $model
     * @return self
     */
    public static function snapshotModelMustBeSubtypeOfModel(string $model): self
    {
        return new self(sprintf("Snapshot Model must be a subclass of %s, %s given",
            Model::class,
            $model
        ));
    }

    /**
     * @return self
     */
    public static function snapshotModelMustImplementSnapshotContractInterface(): self
    {
        return new self(sprintf("Snapshot Model must implement the %s interface",
            SnapshotContract::class
        ));
    }
}