<?php

declare(strict_types=1);

namespace Dvarilek\CompleteModelSnapshot\Support;

use Dvarilek\CompleteModelSnapshot\Exceptions\InvalidRelationException;
use Dvarilek\CompleteModelSnapshot\ValueObjects\RelationDefinition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;

final class RelationValidator
{

    /**
     * @return list<class-string<Relation>>
     */
    public static function getValidRelationTypes(): array
    {
        return [
            BelongsTo::class
        ];
    }

    /**
     * @param  Model $model
     * @param  RelationDefinition $definition
     *
     * @return void
     */
    public static function assertValid(Model $model, RelationDefinition $definition): void
    {
        $relationName = $definition->getName();

        if (!method_exists($model, $relationName)) {
            throw InvalidRelationException::relationNotFound($relationName, $model::class);
        }

        $relation = $model->$relationName();

        if (!in_array($relation::class, self::getValidRelationTypes())) {
            throw InvalidRelationException::invalidRelationType($relationName, $model::class, $relation::class);
        }

    }
}