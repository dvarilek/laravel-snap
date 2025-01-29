<?php

declare(strict_types=1);

use Dvarilek\LaravelSnapshotTree\Tests\Models\TestRootModel;
use Illuminate\Database\Eloquent\SoftDeletes;
use Dvarilek\LaravelSnapshotTree\Helpers\ModelHelper;

test('gets basic timestamp attributes from model', function () {
    $model = new TestRootModel();

    $timestamps = ModelHelper::getTimestampAttributes($model);

    expect($timestamps)
        ->toHaveCount(2)
        ->toBe(['created_at', 'updated_at']);
});

test('gets timestamp attributes including soft deletes', function () {
    $model = new class extends TestRootModel {
        use SoftDeletes;
    };

    $timestamps = ModelHelper::getTimestampAttributes($model);

    expect($timestamps)
        ->toHaveCount(3)
        ->toBe(['created_at', 'updated_at', 'deleted_at']);
});

test('handles models with custom timestamp column names', function () {
    $model = new class extends TestRootModel {
        use SoftDeletes;

        const CREATED_AT = 'creation_date';
        const UPDATED_AT = 'last_modified';
        const DELETED_AT = 'removed_at';
    };

    $timestamps = ModelHelper::getTimestampAttributes($model);

    expect($timestamps)
        ->toHaveCount(3)
        ->toBe(['creation_date', 'last_modified', 'removed_at']);
});