<?php

namespace Dvarilek\LaravelSnapshotTree\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestParent2Model extends Model
{
    protected $table = "test_parent_models_2";

    protected $fillable = [
        'attribute1',
        'attribute2',
        'attribute3',
    ];

    public function children(): HasMany
    {
        return $this->hasMany(TestParent1Model::class, 'parent_model_id');
    }
}