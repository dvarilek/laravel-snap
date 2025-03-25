<?php

namespace Dvarilek\LaravelSnap\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class TestAnotherParent1Model extends Model
{

    protected $table = "test_another_parent_models_1";

    protected $fillable = [
        'attribute1',
        'attribute2',
        'attribute3',
        'another_parent_model_id'
    ];

    public function children(): HasMany
    {
        return $this->hasMany(TestRootModel::class, 'another_parent_model_id');
    }
}