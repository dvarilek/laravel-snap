<?php

declare(strict_types=1);

namespace Dvarilek\LaravelSnapshotTree\Models;

use Dvarilek\LaravelSnapshotTree\Models\Concerns\HasStorageColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Snapshot extends Model
{
    use HasStorageColumn;

    protected $table = 'model_snapshots';

    protected $guarded = [];

    public function origin(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @inheritDoc
     */
    public static function getStorageColumn(): string
    {
        return 'storage';
    }

    /**
     * @inheritDoc
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

}