<?php

use Dvarilek\LaravelSnapshotTree\DTO\AttributeTransferObject;
use Dvarilek\LaravelSnapshotTree\DTO\RelatedAttributeTransferObject;
use Dvarilek\LaravelSnapshotTree\Models\Snapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Casts\{AsStringable, AsCollection};
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Support\Stringable;
use Dvarilek\LaravelSnapshotTree\Tests\Models\TestRootModel;

it('can create snapshot with virtual attributes', function (mixed $value1, mixed $value2) {
    $value1 = Str::random(10);
    $value2 = Str::random(10);
    $value3 = Str::random(10);

    $model = TestRootModel::query()->create();

    $snapshot = $model->snapshot()->create([
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
})->with([
    ['string', 'anotherString'],
    [null, null],
]);

it('can create snapshot with attribute and related attribute transfer object', function (mixed $value1, mixed $value2) {

    $model = TestRootModel::query()->create();

    $snapshot = $model->snapshot()->create([
        'attribute1' => new AttributeTransferObject('attribute1', $value1, null),
        'attribute2' => new RelatedAttributeTransferObject('attribute2', $value2, null, ['relation']),
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
            'relationPath' => ['relation']
        ]);
})->with([
    ['string', 'anotherString'],
    [null, null],
]);

it('can retrieve snapshot with virtual attributes from database', function (mixed $value) {
    $model = TestRootModel::query()->create();

    $snapshot = $model->snapshot()->create([
        'value' => $value,
    ]);

    $snapshot = Snapshot::query()->find($snapshot->getKey());

    expect($snapshot)
        ->toBeInstanceOf(Snapshot::class)
        ->and($snapshot->storage)->toBeNull()
        ->and($snapshot->value)->toBe($value);
})->with([
    'string',
    null
]);

it('can update snapshot virtual attribute after creation', function () {
    $value = Str::random(10);
    $updatedValue1 = Str::random(15);
    $updatedValue2 = Str::random(20);

    $model = TestRootModel::query()->create();

    $snapshot = $model->snapshot()->create([
        'value' => $value,
    ]);

    $snapshot->update([
        'value' => $updatedValue1,
    ]);

    $rawRecordData = DB::table($snapshot->getTable())->first();

    expect($snapshot)
        ->toHaveKey('value')
        ->and($snapshot->storage)->toBeNull()
        ->and($snapshot->value)->toBe($updatedValue1)
        ->and($rawRecordData->storage)->toBeJson()
        ->and($rawRecordData)->not->toHaveProperty('value')
        ->and(json_decode($rawRecordData->storage, true))
        ->toHaveKey('value')
        ->and(json_decode($rawRecordData->storage, true)['value'])
        ->toBe([
            'attribute' => 'value',
            'value' => $updatedValue1,
            'cast' => null,
        ]);

    $snapshot->value = $updatedValue2;
    $snapshot->save();

    $rawRecordData = DB::table($snapshot->getTable())->first();

    expect($snapshot)
        ->toHaveKey('value')
        ->and($snapshot->storage)->toBeNull()
        ->and($snapshot->value)->toBe($updatedValue2)
        ->and($rawRecordData->storage)->toBeJson()
        ->and($rawRecordData)->not->toHaveProperty('value')
        ->and(json_decode($rawRecordData->storage, true))
        ->toHaveKey('value')
        ->and(json_decode($rawRecordData->storage, true)['value'])
        ->toBe([
            'attribute' => 'value',
            'value' => $updatedValue2,
            'cast' => null,
        ]);
});

it('can create create virtual attributes with casts', function () {

    $value1 = Str::random(10);
    $value2 = collect([Str::random(15), Str::random(15)]);

    $model = TestRootModel::query()->create();

    $snapshot = $model->snapshot()->create([
        'attribute1' => new AttributeTransferObject('attribute1', $value1, AsStringable::class),
        'attribute2' => new RelatedAttributeTransferObject('attribute2', $value2, AsCollection::class, ['relation']),
    ]);

    $rawRecordData = DB::table($snapshot->getTable())->first();

    expect($snapshot)
        ->getCasts()->toBe([
            'id' => 'int',
            'storage' => 'array',
            'attribute1' => AsStringable::class,
            'attribute2' => AsCollection::class,
        ])
        ->toHaveKeys([
            'attribute1',
            'attribute2',
        ])
        ->and($snapshot->storage)->toBeNull()
        ->and($snapshot->attribute1)->toBeInstanceOf(Stringable::class)
        ->and($snapshot->attribute1->value)->toBe($value1)
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
            'cast' => AsStringable::class,
        ])
        ->and(json_decode($rawRecordData->storage, true)['attribute2'])
        ->toBe([
            'attribute' => 'attribute2',
            'value' => $value2->all(),
            'cast' => AsCollection::class,
            'relationPath' => ['relation'],
        ]);
});


