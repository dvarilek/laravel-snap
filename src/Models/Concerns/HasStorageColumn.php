<?php

declare(strict_types=1);

namespace Dvarilek\LaravelSnap\Models\Concerns;

use Dvarilek\LaravelSnap\DTO\AttributeTransferObject;
use Dvarilek\LaravelSnap\DTO\Contracts\VirtualAttribute;
use Dvarilek\LaravelSnap\DTO\RelatedAttributeTransferObject;
use Dvarilek\LaravelSnap\Exceptions\InvalidSnapshotException;
use Dvarilek\LaravelSnap\Helpers\TransferObjectHelper;
use Dvarilek\LaravelSnap\Models\Contracts\SnapshotContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * The internal handling of encoding and decoding to and from "StorageColumn" is derived from
 * VirtualColumn laravel package https://github.com/archtechx/virtualcolumn
 *
 * @mixin SnapshotContract&Model
 */
trait HasStorageColumn
{

    protected bool $storageEncoded = false;

    /**
     * This property stores relationPaths for virtual related attributes so that they can
     * be distinguished from regular virtual attributes.
     *
     * @var array<string, list<string>>
     */
    protected array $virtualRelatedAttributesPathCache = [];

    public function initializeHasStorageColumn(): void
    {
        $this->casts[static::getStorageColumn()] = 'array';
    }

    protected function fireModelEvent($event, $halt = true)
    {
        if ($this->storageEncoded) {
            $this->decodeStorageAttributes();
        }

        $result = parent::fireModelEvent($event, $halt);

        $this->callStorageColumnOperation($event);

        return $result;
    }

    protected function callStorageColumnOperation(string $event): void
    {
        if ($event === 'retrieved') {
            $this->decodeStorageAttributes();
        } elseif (!$this->storageEncoded && in_array($event, ['saving', 'creating', 'updating'])) {
            $this->encodeStorableAttributes();
        }
    }

    /**
     * Deserialize attributes from the storage column and set them as model's attributes.
     *
     * @return void
     */
    protected function decodeStorageAttributes(): void
    {
        $storageColumn = static::getStorageColumn();

        foreach ($this->getAttribute($storageColumn) ?? [] as $key => $data) {
            if ($cast = $data['cast'] ?? false) {
                $this->casts[$key] = $cast;
            }

            $this->setAttribute($key, $data['value'] ?? null);
            $this->syncOriginalAttributes($key);
        }

        $this->setAttribute($storageColumn, null);
        $this->syncOriginalAttribute($storageColumn);

        $this->storageEncoded = false;
    }

    /**
     * Serialize model attributes into a storage column.
     *
     * @return void
     */
    protected function encodeStorableAttributes(): void
    {
        $nativeAttributeKeys = static::getNativeAttributes();
        $rawVirtualAttributes = $this->getRawAttributes();
        $virtualAttributes = [];

        // The attributesToArray() method modifies the model's attributes in a way that causes problems on subsequent
        // storage column operations. Notably, it performs casting which is actually essential for proper encoding.
        // Therefore, it needs to be called on a cloned instance of the snapshot model.
        foreach ((clone $this)->attributesToArray() as $key => $data) {
            if (in_array($key, $nativeAttributeKeys)) {
                continue;
            }

            $transferObject = $this->assembleTransferObject($key, $data, $rawVirtualAttributes[$key] ?? null);
            $virtualAttributes[$key] = $transferObject;

            if ($transferObject instanceof RelatedAttributeTransferObject) {
                $this->virtualRelatedAttributesPathCache[$key] = $transferObject->relationPath;
            }

            unset($this->attributes[$key], $this->original[$key]);
        }

        $this->setAttribute(static::getStorageColumn(), $virtualAttributes);
        $this->storageEncoded = true;
    }

    /**
     * Prepare a transfer object for serialization into storage column.
     *
     * @param  string $key
     * @param  mixed $value
     * @param  null|array<string, mixed> $rawVirtualAttribute
     *
     * @return VirtualAttribute
     * @throws InvalidSnapshotException
     */
    protected function assembleTransferObject(string $key, mixed $value, ?array $rawVirtualAttribute = null): VirtualAttribute
    {
        if ($value instanceof VirtualAttribute) {
            return $value;
        }

        $cast = $this->casts[$key] ?? null;
        $cachedRelationPath = $this->virtualRelatedAttributesPathCache[$key] ?? null;

        if (is_array($cachedRelationPath)) {
            // The key must be stripped of its prefix so that the actual attribute name
            // is preserved when stored in the storage column.
            $pathPrefix = implode('_', $cachedRelationPath) . '_';
            $trimmedKey = Str::replaceFirst($pathPrefix, '', $key);

            return new RelatedAttributeTransferObject($trimmedKey, $value, $cast, $cachedRelationPath);
        }

        if ($rawVirtualAttribute === null || TransferObjectHelper::isAttributeTransferObjectFormat($rawVirtualAttribute)) {
            return new AttributeTransferObject($key, $value, $cast);
        }

        throw InvalidSnapshotException::invalidSnapshotAttributeStructure($key, $rawVirtualAttribute);
    }
}