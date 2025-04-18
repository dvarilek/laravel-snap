<?php

declare(strict_types=1);

namespace Dvarilek\LaravelSnap\Models\Contracts;

use Dvarilek\LaravelSnap\Models\Snapshot;
use Illuminate\Database\Eloquent\Model;

/**
 * @see Snapshot Default implementation
 */
interface SnapshotContract
{
    /**
     * Column that holds encoded attributes.
     *
     * @return string
     */
    public static function getStorageColumn(): string;

    /**
     * Column that holds the Snapshot's version.
     *
     * @return string
     */
    public static function getVersionColumn(): string;

    /**
     * Attributes that should not be encoded into the storage column.
     *
     * @return list<string>
     */
    public static function getNativeAttributes(): array;

    /**
     * Return the Snapshot's version.
     *
     * @return ?int
     */
    public function getVersion(): ?int;

    /**
     * Get the snapshots raw attributes without encoding and decoding.
     *
     * @return array<string, mixed>|null
     */
    public function getRawAttributes(): ?array;

    /**
     * Synchronize the origin's model state with this given snapshot.
     *
     * @param  bool $shouldRestoreRelatedAttributes
     *
     * @return ?Model - The origin model
    */
    public function sync(bool $shouldRestoreRelatedAttributes = true): ?Model;
}