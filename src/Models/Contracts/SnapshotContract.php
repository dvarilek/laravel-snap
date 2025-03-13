<?php

declare(strict_types=1);

namespace Dvarilek\CompleteModelSnapshot\Models\Contracts;

use Dvarilek\CompleteModelSnapshot\Models\Snapshot;

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
     * Attributes that should not be encoded into the storage column.
     *
     * @return list<string>
     */
    public static function getNativeAttributes(): array;

    /**
     * Get the snapshots raw attributes without encoding and decoding.
     *
     * @return array<string, mixed>|null
     */
    public function getRawAttributes(): ?array;

}