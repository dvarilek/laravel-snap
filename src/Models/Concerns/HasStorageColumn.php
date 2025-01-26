<?php

namespace Dvarilek\LaravelSnapshotTree\Models\Concerns;

use Dvarilek\LaravelSnapshotTree\DTO\AttributeTransferObject;
use Dvarilek\LaravelSnapshotTree\DTO\Contracts\VirtualAttributeInterface;
use Dvarilek\LaravelSnapshotTree\DTO\RelatedAttributeTransferObject;
use Dvarilek\LaravelSnapshotTree\Helpers\TransferObjectHelper;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * The internal handling of encoding and decoding to and from "StorageColumn" is derived from
 * VirtualColumn laravel package https://github.com/archtechx/virtualcolumn
 *
 * @mixin Model
 */
trait HasStorageColumn
{

    /**
     * This property keeps the track of virtual attribute's full state throughout encodings in one operation.
     *
     * @var array<string, VirtualAttributeInterface>
     */
    protected array $virtualAttributeReferenceMap = [];

    protected bool $storageEncoded = false;

    /**
     * Column that holds encoded attributes.
     *
     * @return string
     */
    abstract public static function getStorageColumn(): string;

    /**
     * Attributes that should not be encoded into the storage column.
     *
     * @return list<string>
     */
    abstract public static function getNativeAttributes(): array;

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
        match (true) {
            $event === 'retrieved' => $this->forceDecodeStorageAttributes(),
            in_array($event, ['saving', 'creating', 'updating']) => $this->encodeStorableAttributes(),
            // Reset reference map after an operation is finished to prevent subsequent conflicts and to make sure that
            // it doesn't pollute the model instance with additional data.
            in_array($event, ['created', 'updated']) => $this->virtualAttributeReferenceMap = [],
            default => null
        };

    }

    protected function forceDecodeStorageAttributes(): void
    {
        $this->storageEncoded = true;

        $this->decodeStorageAttributes();
    }

    /**
     * Deserializes attributes from the storage column back into model attributes.
     *
     * @return void
     */
    protected function decodeStorageAttributes(): void
    {
        if (! $this->storageEncoded) {
            return;
        }

        $storageColumn = static::getStorageColumn();
        $virtualDecodedAttributes = $this->getAttribute($storageColumn);

        foreach ($virtualDecodedAttributes ?? [] as $attribute => $data) {

            $this->addCast($attribute, $data);

            $this->setAttribute($attribute, $data['value'] ?? null);
            $this->syncOriginalAttributes($attribute);
        }

        $this->setAttribute($storageColumn, null);
        $this->syncOriginalAttribute($storageColumn);

        $this->storageEncoded = false;
    }

    /**
     * Serializes model attributes into the designated storage column.
     *
     * @return void
     */
    protected function encodeStorableAttributes(): void
    {
        if ($this->storageEncoded) {
            return;
        }

        $nativeAttributes = static::getNativeAttributes();
        $virtualAttributes = [];

        $dataFromDatabase = $this->getDataFromDatabase();

        foreach ($this->getAttributes() as $attribute => $data) {
            if (in_array($attribute, $nativeAttributes)) {
                continue;
            }

            // Structure data into a serializable and correct format.
            $transferObject = $this->assembleTransferObject($attribute, $data, $dataFromDatabase[$attribute] ?? null);

            if ($transferObject) {
                $virtualAttributes[$attribute] = $transferObject;
                $this->addCast($attribute, $transferObject->toArray());

                // Save the transfer object as a reference so it can be accessed on the second encoding.
                $this->virtualAttributeReferenceMap[$attribute] = $transferObject;
            }

            unset($this->attributes[$attribute], $this->original[$attribute]);
        }

        $this->setAttribute(static::getStorageColumn(), $virtualAttributes);
        $this->storageEncoded = true;
    }

    /**
     * @param  string $attribute
     * @param  mixed $data
     * @param  array|null $dataFromDatabase
     *
     * @return null|VirtualAttributeInterface
     */
    protected function assembleTransferObject(string $attribute, mixed $data, ?array $dataFromDatabase = null): ?VirtualAttributeInterface
    {
        if ($data instanceof VirtualAttributeInterface) {
            return $data;
        }

        // Retrieve any previously stored transfer object for this attribute so it doesn't default to creating a new
        // default attribute transfer object when that was already done on the previous encoding.
        $previousData = $this->virtualAttributeReferenceMap[$attribute] ?? null;
        if ($previousData instanceof VirtualAttributeInterface) {
            return $previousData;
        }

        // When database data is available, reconstruct transfer object based on the data format.
        if ($dataFromDatabase !== null) {
            return match (true) {
                TransferObjectHelper::isAttributeTransferObjectFormat($dataFromDatabase) => $this->createAttributeTransferObject(...func_get_args()),
                TransferObjectHelper::isRelationTransferObjectFormat($dataFromDatabase) => $this->createRelatedAttributeTransferObject(...func_get_args()),
                default => null
            };
        }

        // Default to creating a simple transfer object.
        return $this->createAttributeTransferObject(...func_get_args());
    }

    protected function createAttributeTransferObject(string $attribute, mixed $data, ?array $dataFromDatabase = null): AttributeTransferObject
    {
        return new AttributeTransferObject(
            attribute: $attribute,
            value: $data,
            cast: $dataFromDatabase['cast'] ?? null,
        );
    }

    protected function createRelatedAttributeTransferObject(string $attribute, mixed $data, ?array $dataFromDatabase = null): RelatedAttributeTransferObject
    {
        return new RelatedAttributeTransferObject(
            attribute: $attribute,
            value: $data,
            cast: $dataFromDatabase['cast'],
            relationPath: $dataFromDatabase['relationPath'],
        );
    }

    protected function addCast(string $attribute, array $data): void
    {
        if ($cast = $data['cast'] ?? false) {
            $this->casts[$attribute] = $cast;
        }
    }

    protected function getDataFromDatabase(): ?array
    {
        $dataFromDatabase = DB::table($this->getTable())
            ->find($this->getKey())
            ?->{static::getStorageColumn()};

        return $dataFromDatabase ? json_decode($dataFromDatabase, true) : null;
    }
}