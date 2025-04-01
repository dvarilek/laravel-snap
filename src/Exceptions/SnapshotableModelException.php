<?php

declare(strict_types=1);

namespace Dvarilek\LaravelSnap\Exceptions;

use Illuminate\Database\Eloquent\Model;

final class SnapshotableModelException extends \Exception
{

    /**
     * @param  class-string<Model> $model
     *
     * @return self
     */
    public static function missingCurrentVersionColumn(string $model): self
    {
        return new self(sprintf("Versioning a Model '%s' requires having a current version column configured on that model",
            $model
        ));
    }

    /**
     * @param  int $steps
     * @param  class-string<Model> $model
     *
     * @return self
     */
    public static function invalidNumberOfSteps(int $steps, string $model): self
    {
        return new self(sprintf("Invalid number of steps provided '%s' while reverting to a Snapshot on model '%s'",
            $steps,
            $model
        ));
    }
}