<?php

namespace Dvarilek\LaravelSnapshotTree\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Snapshot extends Model
{

    protected $table = 'model_snapshots';

    protected $fillable = [
        'origin_id',
        'origin_type',
        'data'
    ];

    public function origin(): MorphTo
    {
        return $this->morphTo();
    }
}