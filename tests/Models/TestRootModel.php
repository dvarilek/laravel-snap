<?php

namespace Dvarilek\LaravelSnapshotTree\Tests\Models;

use Dvarilek\LaravelSnapshotTree\Models\Concerns\Snapshotable;
use Dvarilek\LaravelSnapshotTree\ValueObjects\SnapshotDefinition;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TestRootModel extends Model
{
    use Snapshotable;

    protected $table = 'test_root_models';

    protected $fillable = [
        'attribute1',
        'attribute2',
        'attribute3',
        'extraAttribute1',
        'extraAttribute2',
        'parent_model_id',
        'another_parent_model_id'
    ];

    protected $hidden = [
        'hidden1'
    ];

    public static function getSnapshotDefinition(): SnapshotDefinition
    {
        return SnapshotDefinition::make()
            ->captureAll();
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(TestParent1Model::class, 'parent_model_id');
    }

    public function anotherParent(): BelongsTo
    {
        return $this->belongsTo(TestAnotherParent1Model::class, 'another_parent_model_id');
    }
}