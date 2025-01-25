<?php

namespace Dvarilek\LaravelSnapshotTree\Helpers;

use Dvarilek\LaravelSnapshotTree\DTO\RelatedAttributeTransferObject;

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
}