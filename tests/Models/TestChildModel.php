<?php

namespace Dvarilek\LaravelSnapshotTree\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestChildModel extends Model
{

    protected $table = "test_child_models_1";

    protected $fillable = [
        'attribute1',
        'attribute2',
        'attribute3',
        'parent_model_id'
    ];

    public function childModel(): BelongsTo
    {
        return $this->belongsTo(TestChild2Model::class, 'parent_model_id');
    }
}