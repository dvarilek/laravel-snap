<?php

namespace Dvarilek\LaravelSnap\Tests\Models;

use Dvarilek\LaravelSnap\Models\Concerns\Snapshotable;
use Dvarilek\LaravelSnap\ValueObjects\SnapshotDefinition;
use Illuminate\Database\Eloquent\Casts\AsStringable;
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
        'castable1',
        'extraAttribute1',
        'extraAttribute2',
        'parent_model_id',
        'another_parent_model_id'
    ];

    protected $hidden = [
        'hidden1'
    ];

    protected $casts = [
        'castable1' => AsStringable::class,
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