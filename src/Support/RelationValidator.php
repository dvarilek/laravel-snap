<?php

declare(strict_types=1);

namespace Dvarilek\CompleteModelSnapshot\Support;

use Dvarilek\CompleteModelSnapshot\ValueObjects\RelationDefinition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\RelationNotFoundException;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;

final class RelationValidator
{

    /**
     * @var list<class-string<Relation>>
     */
    protected static array $allowedRelationTypes = [
        BelongsTo::class,
    ];

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
            self::throwRelationNotFoundException($relationName, $model::class);
        }

        $relation = $model->$relationName();
        if (!in_array($relation::class, self::$allowedRelationTypes)) {
            self::throwIncorrectRelationTypeException($relationName, $model::class, $relation::class);
        }

    }

    /**
     * @param  string $relationName
     * @param  class-string<Model> $modelClass
     *
     * @return never
     */
    public static function throwRelationNotFoundException(string $relationName, string $modelClass): never
    {
        throw new RelationNotFoundException(sprintf('The relationship %s does not exist on model %s.',
                $relationName,
                $modelClass
            )
        );
    }

    /**
     * @param  string $relationName
     * @param  class-string<Model> $modelClass
     * @param  class-string<Relation> $relationType
     *
     * @return never
     */
    public static function throwIncorrectRelationTypeException(string $relationName, string $modelClass, string $relationType): never
    {
        throw new \InvalidArgumentException(sprintf('The relationship %s on model %s must be of type %s, %s provided.',
                $relationName,
                $modelClass,
                self::$allowedRelationTypes[0], // TODO use implode or something when more relation are added
                $relationType
            )
        );
    }
}