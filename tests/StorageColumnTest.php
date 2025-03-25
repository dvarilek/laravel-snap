<?php

declare(strict_types=1);

use Dvarilek\LaravelSnap\DTO\AttributeTransferObject;
use Dvarilek\LaravelSnap\DTO\RelatedAttributeTransferObject;
use Dvarilek\LaravelSnap\Models\Snapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Casts\{AsStringable, AsCollection};
use Illuminate\Support\Collection;
use Illuminate\Support\Stringable;
use Dvarilek\LaravelSnap\Tests\Models\TestRootModel;

test('snapshot can be created with regular virtual attributes with values', function () {
    $model = TestRootModel::query()->create();

    $value1 = 'firstValue';
    $value2 = null;

    $snapshot = $model->snapshots()->create([
        'attribute1' => $value1,
        'attribute2' => $value2,
    ]);

    $rawRecordData = DB::table($snapshot->getTable())->first();

    expect($snapshot)
        ->toHaveKeys([
            'attribute1',
            'attribute2',
        ])
        ->and($snapshot->storage)->toBeNull()
        ->and($snapshot->attribute1)->toBe($value1)
        ->and($snapshot->attribute2)->toBe($value2)
        ->and($rawRecordData->storage)->toBeJson()
        ->and($rawRecordData)->not->toHaveProperty('attribute1')
        ->and($rawRecordData)->not->toHaveProperty('attribute2')
        ->and(json_decode($rawRecordData->storage, true))
        ->toHaveKeys([
            'attribute1',
            'attribute2',
        ])
        ->and(json_decode($rawRecordData->storage, true)['attribute1'])
        ->toBe([
            'attribute' => 'attribute1',
            'value' => $value1,
            'cast' => null,
        ])
        ->and(json_decode($rawRecordData->storage, true)['attribute2'])
        ->toBe([
            'attribute' => 'attribute2',
            'value' => $value2,
            'cast' => null,
        ]);
});

test('snapshot can be created with regular transfer object virtual attributes with casts', function () {
    $model = TestRootModel::query()->create();

    $value1 = "firstValue1";
    $value2 = "secondValue2";

    $snapshot = $model->snapshots()->create([
        'attribute1' => new AttributeTransferObject('attribute1', $value1, AsStringable::class),
        'attribute2' => new AttributeTransferObject('attribute2', [$value2], 'array'),
    ]);

    $rawRecordData = DB::table($snapshot->getTable())->first();

    expect($snapshot)
        ->toHaveKeys([
            'attribute1',
            'attribute2',
        ])
        ->and($snapshot->storage)->toBeNull()
        ->and($snapshot->attribute1)->toBeInstanceOf(Stringable::class)
        ->and($snapshot->attribute1->value)->toBe($value1)
        ->and($snapshot->attribute2)->toBe([$value2])
        ->and($rawRecordData->storage)->toBeJson()
        ->and($rawRecordData)->not->toHaveProperty('attribute1')
        ->and($rawRecordData)->not->toHaveProperty('attribute2')
        ->and(json_decode($rawRecordData->storage, true))
        ->toHaveKeys([
            'attribute1',
            'attribute2',
        ])
        ->and(json_decode($rawRecordData->storage, true)['attribute1'])
        ->toBe([
            'attribute' => 'attribute1',
            'value' => $value1,
            'cast' => AsStringable::class,
        ])
        ->and(json_decode($rawRecordData->storage, true)['attribute2'])
        ->toBe([
            'attribute' => 'attribute2',
            'value' => [$value2],
            'cast' => 'array',
        ]);
});

test('snapshot can be created with related transfer object virtual attributes with casts', function () {
    $model = TestRootModel::query()->create();

    $value1 = 'firstValue1';
    $value2 = null;

    $snapshot = $model->snapshots()->create([
        'relation_attribute1' => new RelatedAttributeTransferObject('attribute1', $value1, AsStringable::class, ['relation']),
        'relation_attribute2' => new RelatedAttributeTransferObject('attribute2', $value2, null, ['relation']),
    ]);

    $rawRecordData = DB::table($snapshot->getTable())->first();

    expect($snapshot)
        ->toHaveKeys([
            'relation_attribute1',
            'relation_attribute2',
        ])
        ->and($snapshot->storage)->toBeNull()
        ->and($snapshot->relation_attribute1)->toBeInstanceOf(Stringable::class)
        ->and($snapshot->relation_attribute1->value)->toBe($value1)
        ->and($snapshot->relation_attribute2)->toBe($value2)
        ->and($rawRecordData->storage)->toBeJson()
        ->and($rawRecordData)->not->toHaveProperty('relation_attribute1')
        ->and($rawRecordData)->not->toHaveProperty('relation_attribute2')
        ->and(json_decode($rawRecordData->storage, true))
        ->toHaveKeys([
            'relation_attribute1',
            'relation_attribute2',
        ])
        ->and(json_decode($rawRecordData->storage, true)['relation_attribute1'])
        ->toBe([
            'attribute' => 'attribute1',
            'value' => $value1,
            'cast' => AsStringable::class,
            'relationPath' => ['relation'],
        ])
        ->and(json_decode($rawRecordData->storage, true)['relation_attribute2'])
        ->toBe([
            'attribute' => 'attribute2',
            'value' => $value2,
            'cast' => null,
            'relationPath' => ['relation']
        ]);
});

test('snapshot can be created with both regular and related attribute transfer object', function () {
    $model = TestRootModel::query()->create();

    $value1 = 'firstValue1';
    $value2 = null;

    $snapshot = $model->snapshots()->create([
        'attribute1' => $value1,
        'relation_attribute1' => new RelatedAttributeTransferObject('attribute1', $value2, null, ['relation']),
    ]);

    $rawRecordData = DB::table($snapshot->getTable())->first();

    expect($snapshot)
        ->toHaveKeys([
            'attribute1',
            'relation_attribute1',
        ])
        ->and($snapshot->storage)->toBeNull()
        ->and($snapshot->attribute1)->toBe($value1)
        ->and($snapshot->relation_attribute1)->toBe($value2)
        ->and($rawRecordData->storage)->toBeJson()
        ->and($rawRecordData)->not->toHaveProperty('attribute1')
        ->and($rawRecordData)->not->toHaveProperty('relation_attribute1')
        ->and(json_decode($rawRecordData->storage, true))
        ->toHaveKeys([
            'attribute1',
            'relation_attribute1',
        ])
        ->and(json_decode($rawRecordData->storage, true)['attribute1'])
        ->toBe([
            'attribute' => 'attribute1',
            'value' => $value1,
            'cast' => null
        ])
        ->and(json_decode($rawRecordData->storage, true)['relation_attribute1'])
        ->toBe([
            'attribute' => 'attribute1',
            'value' => $value2,
            'cast' => null,
            'relationPath' => ['relation']
        ]);
});

test('created snapshot can be retrieved from database', function () {
    $model = TestRootModel::query()->create();

    $value1 = null;
    $value2 = ['array'];

    $snapshot = $model->snapshots()->create([
        'attribute1' => $value1,
        'attribute2' => new AttributeTransferObject('attribute2', $value2, 'array')
    ]);

    $rawRecordData = DB::table($snapshot->getTable())->first();
    $snapshot = Snapshot::query()->find($snapshot->getKey());

    expect($snapshot)
        ->toBeInstanceOf(Snapshot::class)
        ->and($snapshot->storage)->toBeNull()
        ->and($snapshot->attribute1)->toBe($value1)
        ->and($snapshot->attribute2)->toBe($value2)
        ->and(json_decode($rawRecordData->storage, true)['attribute1'])
        ->toBe([
            'attribute' => 'attribute1',
            'value' => $value1,
            'cast' => null,
        ])
        ->and(json_decode($rawRecordData->storage, true)['attribute2'])
        ->toBe([
            'attribute' => 'attribute2',
            'value' => $value2,
            'cast' => 'array',
        ]);
});

test('snapshot virtual attributes can be updated after creation', function () {
    $initialValue = 'initialValue';
    $updatedValue1 = 'updatedValue1';
    $updatedValue2 = 'updatedValue2';

    $model = TestRootModel::query()->create();

    $snapshot = $model->snapshots()->create([
        'attribute1' => $initialValue,
    ]);

    $snapshot->update([
        'attribute1' => $updatedValue1,
    ]);

    $rawRecordData = DB::table($snapshot->getTable())->first();

    expect($snapshot)
        ->toHaveKey('attribute1')
        ->and($snapshot->storage)->toBeNull()
        ->and($snapshot->attribute1)->toBe($updatedValue1)
        ->and($rawRecordData->storage)->toBeJson()
        ->and($rawRecordData)->not->toHaveProperty('attribute1')
        ->and(json_decode($rawRecordData->storage, true))
        ->toHaveKey('attribute1')
        ->and(json_decode($rawRecordData->storage, true)['attribute1'])
        ->toBe([
            'attribute' => 'attribute1',
            'value' => $updatedValue1,
            'cast' => null,
        ]);

    $snapshot->attribute1 = $updatedValue2;
    $snapshot->save();

    $rawRecordData = DB::table($snapshot->getTable())->first();

    expect($snapshot)
        ->toHaveKey('attribute1')
        ->and($snapshot->storage)->toBeNull()
        ->and($snapshot->attribute1)->toBe($updatedValue2)
        ->and($rawRecordData->storage)->toBeJson()
        ->and($rawRecordData)->not->toHaveProperty('attribute1')
        ->and(json_decode($rawRecordData->storage, true))
        ->toHaveKey('attribute1')
        ->and(json_decode($rawRecordData->storage, true)['attribute1'])
        ->toBe([
            'attribute' => 'attribute1',
            'value' => $updatedValue2,
            'cast' => null,
        ]);
});

test('snapshot can have virtual attributes that are casted to Collection and array', function () {
    $value1 = ['array'];
    $value2 = collect(['collection']);

    $model = TestRootModel::query()->create();

    $snapshot = $model->snapshots()->create([
        'attribute1' => new AttributeTransferObject('attribute1', $value1, 'array'),
        'attribute2' => new AttributeTransferObject('attribute2', $value2, AsCollection::class),
    ]);

    $rawRecordData = DB::table($snapshot->getTable())->first();

    expect($snapshot)
        ->getCasts()->toBe([
            'id' => 'int',
            'storage' => 'array',
            'attribute1' => 'array',
            'attribute2' => AsCollection::class,
        ])
        ->toHaveKeys([
            'attribute1',
            'attribute2',
        ])
        ->and($snapshot->storage)->toBeNull()
        ->and($snapshot->attribute1)->toBeArray()
        ->and($snapshot->attribute1)->toBe($value1)
        ->and($snapshot->attribute2)->toBeInstanceOf(Collection::class)
        ->and($snapshot->attribute2->all())->toBe($value2->all())
        ->and($rawRecordData->storage)->toBeJson()
        ->and($rawRecordData)->not->toHaveProperty('attribute1')
        ->and($rawRecordData)->not->toHaveProperty('attribute2')
        ->and(json_decode($rawRecordData->storage, true))
        ->toHaveKeys([
            'attribute1',
            'attribute2',
        ])
        ->and(json_decode($rawRecordData->storage, true)['attribute1'])
        ->toBe([
            'attribute' => 'attribute1',
            'value' => $value1,
            'cast' => 'array',
        ])
        ->and(json_decode($rawRecordData->storage, true)['attribute2'])
        ->toBe([
            'attribute' => 'attribute2',
            'value' => $value2->all(),
            'cast' => AsCollection::class,
        ]);
});