<?php

declare(strict_types=1);

namespace Dvarilek\CompleteModelSnapshot\Exceptions;

use Dvarilek\CompleteModelSnapshot\Support\RelationValidator;
use Illuminate\Database\Eloquent\Model;

final class InvalidRelationException extends \Exception
{

    /**
     * @param string $relationName
     * @param class-string<Model> $model
     *
     * @return self
     */
    public static function relationNotFound(string $relationName, string $model): self
    {
        return new self(sprintf('The relationship %s does not exist on model %s.',
                $relationName,
                $model
            )
        );
    }

    /**
     * @param  string $relationName
     * @param  class-string<Model> $model
     * @param  string $relationType
     *
     * @return self
     */
    public static function invalidRelationType(string $relationName, string $model, string $relationType): self
    {
        return new self(sprintf('The relationship %s on model %s must be of type %s, %s provided.',
            $relationName,
            $model,
            implode(', ', RelationValidator::getValidRelationTypes()),
            $relationType
        ));
    }
}