<?php

namespace Dvarilek\LaravelSnapshotTree\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TestParent1Model extends Model
{

    protected $table = "test_parent_models_1";

    protected $fillable = [
        'attribute1',
        'attribute2',
        'attribute3',
        'parent_model_id'
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(TestParent2Model::class, 'parent_model_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(TestRootModel::class, 'parent_model_id');
    }
}