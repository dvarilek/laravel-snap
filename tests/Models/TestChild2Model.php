<?php

namespace Dvarilek\LaravelSnapshotTree\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class TestChild2Model extends Model
{
    protected $table = "test_child_models_2";

    protected $fillable = [
        'attribute1',
        'attribute2',
        'attribute3',
        'parent_model_id'
    ];
}