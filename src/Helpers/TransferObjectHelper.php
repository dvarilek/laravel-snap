<?php

declare(strict_types=1);

namespace Dvarilek\LaravelSnap\Helpers;

use Dvarilek\LaravelSnap\DTO\AttributeTransferObject;
use Dvarilek\LaravelSnap\DTO\RelatedAttributeTransferObject;
use Illuminate\Database\Eloquent\Model;

final class TransferObjectHelper
{

    /**
     * Determine if the provided data format adheres to RelatedAttributeTransferObject structure.
     *
     * @param  mixed $data
     *
     * @return bool
     */
    public static function isRelationTransferObjectFormat(mixed $data): bool
    {
        return is_array($data)
            && array_key_exists('relationPath', $data)
            && array_key_exists('value', $data)
            && array_key_exists('attribute', $data)
            && array_key_exists('cast', $data);
    }

    /**
     * Determine if the provided data format adheres to AttributeTransferObject structure.
     *
     * @param  mixed $data
     *
     * @return bool
     */
    public static function isAttributeTransferObjectFormat(mixed $data): bool
    {
        return is_array($data)
            && ! array_key_exists('relationPath', $data)
            && array_key_exists('value', $data)
            && array_key_exists('attribute', $data)
            && array_key_exists('cast', $data);
    }

    /**
     * @param  RelatedAttributeTransferObject $transferObject
     *
     * @return string
     */
    public static function createQualifiedRelationName(RelatedAttributeTransferObject $transferObject): string
    {
        return implode('_', $transferObject->relationPath) . '_' . $transferObject->attribute;
    }

    /**
     * @param  Model $model
     * @param  array<string, AttributeTransferObject> | array<string, RelatedAttributeTransferObject> $transferObjects
     *
     * @return array<string, mixed>
     */
    public static function convertTransferObjectAttributesToModelAttributes(Model $model, array $transferObjects): array
    {
        $convertedAttributes = [];

        $modelPrimaryKeyName = $model->getKeyName();
        $modelFillables = $model->getFillable();

        foreach ($transferObjects as $key => $transferObject) {
            if (! in_array($transferObject->attribute, $modelFillables)) {
                continue;
            }

            // Primary key is irrelevant
            if ($transferObject->attribute === $modelPrimaryKeyName) {
                continue;
            }

            // Ensure the attribute gets cast as we didn't perform that
            $model->setAttribute($key, $transferObject->value);

            $convertedAttributes[$key] = $model->getAttribute($key);
        }

        return $convertedAttributes;
    }
}