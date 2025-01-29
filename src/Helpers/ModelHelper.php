<?php

declare(strict_types=1);

namespace Dvarilek\CompleteModelSnapshot\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ModelHelper
{
    /**
     * Return the model's timestamp attributes.
     *
     * @param Model $model
     *
     * @return list<string>
     */
    public static function getTimestampAttributes(Model $model): array
    {
        $attributes =  [
            $model->getCreatedAtColumn(),
            $model->getUpdatedAtColumn()
        ];

        if (in_array(SoftDeletes::class, class_uses_recursive($model)) && method_exists($model, 'getDeletedAtColumn')) {
            $attributes[] = $model->getDeletedAtColumn();
        }

        return $attributes;
    }
}