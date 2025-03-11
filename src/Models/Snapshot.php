<?php

declare(strict_types=1);

namespace Dvarilek\CompleteModelSnapshot\Models;

use Dvarilek\CompleteModelSnapshot\Models\Concerns\HasStorageColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Snapshot extends Model
{
    use HasStorageColumn;

    protected $table = 'model_snapshots';

    protected $guarded = [];

    /**
     * Column that holds encoded attributes.
     *
     * @return string
     */
    public static function getStorageColumn(): string
    {
        return 'storage';
    }

    /**
     * Attributes that should not be encoded into the storage column.
     *
     * @return list<string>
     */
    public static function getNativeAttributes(): array
    {
        return [
            'id',
            'origin_id',
            'storage',
            'origin_type',
            'created_at',
            'updated_at',
        ];
    }

    public function origin(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the snapshots raw attributes without encoding and decoding.
     *
     * @return array<string, mixed>|null
     */
    public function getRawAttributes(): ?array
    {
        $data = $this->newQuery()->toBase()->value(static::getStorageColumn());

        return $data ? json_decode($data, true) : null;
    }
}