<?php

declare(strict_types=1);

namespace Dvarilek\LaravelSnap\Models;

use Dvarilek\LaravelSnap\Models\Concerns\HasStorageColumn;
use Dvarilek\LaravelSnap\Models\Contracts\SnapshotContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Snapshot extends Model implements SnapshotContract
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
     * Column that holds the Snapshot's version.
     *
     * @return string
     */
    public static function getVersionColumn(): string
    {
        return 'version';
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
            'version',
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
     * Return the Snapshot's version.
     *
     * @return ?int
     */
    public function getVersion(): ?int
    {
        return $this->getAttribute(static::getVersionColumn());
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

    /**
     * Synchronize the origin's model state with this given snapshot.
     *
     * @param  bool $shouldRestoreRelatedAttributes
     *
     * @return ?Model - The origin model
     */
    public function sync(bool $shouldRestoreRelatedAttributes = true): ?Model
    {
        /** @var Model $origin */
        $origin = $this->origin()->get()->first();

        /** @phpstan-ignore method.notFound */
        return $origin->revertTo($this, $shouldRestoreRelatedAttributes);
    }

    public static function booted(): void
    {
        static::creating(function (self $snapshot) {
            if ($snapshot->getVersion() === null) {
                $snapshot->{static::getVersionColumn()} = 1;
            }
        });
    }
}