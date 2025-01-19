<?php

namespace Dvarilek\LaravelSnapshotTree\Tests\Models;

use Dvarilek\LaravelSnapshotTree\Models\Concerns\Snapshotable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestRootModel extends Model
{
    use Snapshotable;

    protected $table = 'test_root_models';

    protected $fillable = [
        'attribute1',
        'attribute2',
        'attribute3'
    ];

    public function childModel(): BelongsTo
    {
        return $this->belongsTo(TestRootModel::class, 'parent_model_id');
    }
}