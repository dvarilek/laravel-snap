<?php

namespace Dvarilek\CompleteModelSnapshot\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TestParent1Model extends Model
{

    protected $table = "test_parent_models_1";

    protected $fillable = [
        'attribute1',
        'attribute2',
        'attribute3',
        'castable1',
        'parent_model_id'
    ];

    protected $casts = [
        'castable1' => 'integer',
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