<?php

use Dvarilek\LaravelSnapshotTree\Tests\Models\{TestRootModel, TestParent1Model, TestParent2Model, TestAnotherParent1Model};
use Dvarilek\LaravelSnapshotTree\DTO\{AttributeTransferObject, RelatedAttributeTransferObject};
use Illuminate\Support\Str;
use Dvarilek\LaravelSnapshotTree\Services\Contracts\AttributeCollectorInterface;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Dvarilek\LaravelSnapshotTree\Services\SnapshotAttributeCollector;
use Dvarilek\LaravelSnapshotTree\ValueObjects\RelationDefinition;
use Dvarilek\LaravelSnapshotTree\ValueObjects\SnapshotDefinition;
use Illuminate\Database\Eloquent\Model;

test('default attribute collector is an instance of SnapshotAttributeCollector', function () {
    $collector = app(AttributeCollectorInterface::class);

    expect($collector)->toBeInstanceOf(SnapshotAttributeCollector::class);
});

it('can collect main model attributes', function () {
    $collector = app(AttributeCollectorInterface::class);

    $value1 = Str::random(10);
    $value2 = Str::random(10);
    $value3 = Str::random(10);

    $model = TestRootModel::query()->forceCreate([
        'attribute1' => $value1,
        'attribute2' => $value2,
        'attribute3' => $value3,
    ]);

    $definition = $model::getSnapshotDefinition();
    $attributes = $collector->getModelAttributes($model, $definition);
    unset($attributes['created_at'], $attributes['updated_at']);

    expect($attributes)
        ->toHaveCount(4)
        ->toBe([
            'attribute1' => $value1,
            'attribute2' => $value2,
            'attribute3' => $value3,
            'id' => 1,
        ]);
});

it('can capture only specific attributes', function () {
    $collector = app(AttributeCollectorInterface::class);

    $value1 = Str::random(10);
    $value2 = Str::random(10);
    $value3 = Str::random(10);

    $model = TestRootModel::query()->create([
        'attribute1' => $value1,
        'attribute2' => $value2,
        'attribute3' => $value3,
    ]);

    $definition = SnapshotDefinition::make()
        ->capture(['attribute1', 'attribute2']);

    $attributes = $collector->getModelAttributes($model, $definition);
    unset($attributes['created_at'], $attributes['updated_at']);

    expect($attributes)
        ->toHaveCount(2)
        ->toBe([
            'attribute1' => $value1,
            'attribute2' => $value2,
        ]);
});

test('primary key is not included in model attributes', function () {
    $collector = app(AttributeCollectorInterface::class);

    $model = TestRootModel::query()->create();
    $definition = SnapshotDefinition::make()
        ->capture(['id']);

    $attributes = $collector->collectAttributes($model, $definition);

    expect($attributes)->toHaveCount(0);
});

test('primary key is always included in related model attributes', function () {
    $collector = app(AttributeCollectorInterface::class);


    $parentModel = TestParent1Model::query()->create();
    $relatedModel = $parentModel->children()->create();

    $definition = SnapshotDefinition::make()
        ->captureRelations([
            RelationDefinition::from('parent')
                ->exclude(['id'])
        ]);

    $attributes = $collector->getRelatedAttributes($relatedModel, $definition);

    expect($attributes)
        ->toHaveCount(1)
        ->toHaveKey('parent_id')
        ->and($attributes['parent_id'])->toBeInstanceOf(RelatedAttributeTransferObject::class)
        ->attribute->toBe('id')
        ->value->toBe(1)
        ->cast->toBe('int')
        ->relationPath->toBe(['parent']);
});

test('hidden attributes are not captured by default', function () {
    $collector = app(AttributeCollectorInterface::class);

    $hidden = Str::random(10);

    $model = TestRootModel::query()->forceCreate([
        'hidden1' => $hidden
    ]);

    $definition = SnapshotDefinition::make()
        ->capture(['hidden1']);

    $attributes = $collector->getModelAttributes($model, $definition);

    expect($attributes)->toBeEmpty();
});

it('can capture hidden attributes when specified', function () {
    $collector = app(AttributeCollectorInterface::class);

    $hidden = Str::random(10);

    $model = TestRootModel::query()->forceCreate([
        'hidden1' => $hidden
    ]);

    $definition = SnapshotDefinition::make()
        ->captureHiddenAttributes()
        ->capture(['hidden1']);

    $attributes = $collector->getModelAttributes($model, $definition);

    expect($attributes)
        ->toHaveCount(1)
        ->toBe([
            'hidden1' => $hidden
        ]);
});

it('can exclude specific attributes', function () {
    $collector = app(AttributeCollectorInterface::class);

    $value1 = Str::random(10);
    $value2 = Str::random(10);
    $value3 = Str::random(10);

    $model = TestRootModel::query()->create([
        'attribute1' => $value1,
        'attribute2' => $value2,
        'attribute3' => $value3,
    ]);

    $definition = SnapshotDefinition::make()
        ->captureAll()
        ->exclude([
            'id',
            'attribute1',
            'attribute2',
            'created_at',
            'updated_at'
        ]);

    $attributes = $collector->getModelAttributes($model, $definition);

    expect($attributes)
        ->toHaveCount(1)
        ->toBe([
            'attribute3' => $value3,
        ]);
});

it('can capture and exclude attributes at the same time', function () {
    $collector = app(AttributeCollectorInterface::class);

    $value1 = Str::random(10);
    $value2 = Str::random(10);
    $value3 = Str::random(10);

    $model = TestRootModel::query()->create([
        'attribute1' => $value1,
        'attribute2' => $value2,
        'attribute3' => $value3,
    ]);

    $definition = SnapshotDefinition::make()
        ->capture(['attribute1', 'attribute2'])
        ->exclude(['attribute2', 'created_at', 'updated_at']);

    $attributes = $collector->getModelAttributes($model, $definition);

    expect($attributes)
        ->toHaveCount(1)
        ->toBe([
            'attribute1' => $value1,
        ]);
});

it('can capture attributes from related model', function () {
    $collector = app(AttributeCollectorInterface::class);

    $value1 = Str::random(10);
    $value2 = Str::random(10);
    $value3 = Str::random(10);

    $relatedModel = TestParent1Model::query()->create([
        'attribute1' => $value1,
        'attribute2' => $value2,
        'attribute3' => $value3,
    ]);

    $childModel = $relatedModel->children()->create();

    $definition = SnapshotDefinition::make()
        ->captureRelations([
            RelationDefinition::from('parent')
                ->capture([
                    'attribute1',
                    'attribute2',
                    'attribute3',
                ])
        ]);

    $attributes = $collector->getRelatedAttributes($childModel, $definition);

    expect($attributes)
        ->toHaveCount(4)
        ->toHaveKeys([
            'parent_id',
            'parent_attribute1',
            'parent_attribute2',
            'parent_attribute3',
        ])
        ->and($attributes['parent_id'])->toBeInstanceOf(RelatedAttributeTransferObject::class)
        ->attribute->toBe('id')
        ->value->toBe(1)
        ->relationPath->toBe(['parent'])
        ->and($attributes['parent_attribute1'])->toBeInstanceOf(RelatedAttributeTransferObject::class)
        ->attribute->toBe('attribute1')
        ->value->toBe($value1)
        ->relationPath->toBe(['parent'])
        ->and($attributes['parent_attribute2'])->toBeInstanceOf(RelatedAttributeTransferObject::class)
        ->attribute->toBe('attribute2')
        ->value->toBe($value2)
        ->relationPath->toBe(['parent'])
        ->and($attributes['parent_attribute3'])->toBeInstanceOf(RelatedAttributeTransferObject::class)
        ->attribute->toBe('attribute3')
        ->value->toBe($value3)
        ->relationPath->toBe(['parent']);
});

it('can capture attributes from multiple relations on the same level', function () {
    $collector = app(AttributeCollectorInterface::class);

    $parentValue1 = Str::random(10);
    $parentValue2 = Str::random(10);
    $anotherParentValue1 = Str::random(10);
    $anotherParentValue2 = Str::random(10);

    $parentModel = TestParent1Model::query()->create([
        'attribute1' => $parentValue1,
        'attribute2' => $parentValue2,
    ]);

    $anotherParentModel = TestAnotherParent1Model::query()->create([
        'attribute1' => $anotherParentValue1,
        'attribute2' => $anotherParentValue2,
    ]);

    $childModel = $parentModel->children()->create([
        'another_parent_model_id' => $anotherParentModel->getKey(),
    ]);

    $definition = SnapshotDefinition::make()
        ->captureRelations([
            RelationDefinition::from('parent')
                ->capture([
                    'attribute1',
                    'attribute2',
                ]),
            RelationDefinition::from('anotherParent')
                ->capture([
                    'attribute1',
                    'attribute2',
                ])
        ]);

    $attributes = $collector->getRelatedAttributes($childModel, $definition);

    expect($attributes)
        ->toHaveCount(6)
        ->toHaveKeys([
            'parent_id',
            'parent_attribute1',
            'parent_attribute2',
            'anotherParent_id',
            'anotherParent_attribute1',
            'anotherParent_attribute2',
        ])
        ->and($attributes['parent_id'])->toBeInstanceOf(RelatedAttributeTransferObject::class)
        ->attribute->toBe('id')
        ->value->toBe(1)
        ->relationPath->toBe(['parent'])
        ->and($attributes['parent_attribute1'])
        ->toBeInstanceOf(RelatedAttributeTransferObject::class)
        ->attribute->toBe('attribute1')
        ->value->toBe($parentValue1)
        ->relationPath->toBe(['parent'])
        ->and($attributes['parent_attribute2'])
        ->toBeInstanceOf(RelatedAttributeTransferObject::class)
        ->attribute->toBe('attribute2')
        ->value->toBe($parentValue2)
        ->relationPath->toBe(['parent'])
        ->and($attributes['anotherParent_id'])->toBeInstanceOf(RelatedAttributeTransferObject::class)
        ->attribute->toBe('id')
        ->value->toBe(1)
        ->relationPath->toBe(['anotherParent'])
        ->and($attributes['anotherParent_attribute1'])
        ->toBeInstanceOf(RelatedAttributeTransferObject::class)
        ->attribute->toBe('attribute1')
        ->value->toBe($anotherParentValue1)
        ->relationPath->toBe(['anotherParent'])
        ->and($attributes['anotherParent_attribute2'])
        ->toBeInstanceOf(RelatedAttributeTransferObject::class)
        ->attribute->toBe('attribute2')
        ->value->toBe($anotherParentValue2)
        ->relationPath->toBe(['anotherParent']);
});

it('can capture attributes from nested related models', function () {
    $collector = app(AttributeCollectorInterface::class);

    $parentValue1 = Str::random(10);
    $parentValue2 = Str::random(10);
    $parentValue3 = Str::random(10);

    $grandparentValue1 = Str::random(10);
    $grandparentValue2 = Str::random(10);
    $grandparentValue3 = Str::random(10);

    $grandparentModel = TestParent2Model::query()->create([
        'attribute1' => $grandparentValue1,
        'attribute2' => $grandparentValue2,
        'attribute3' => $grandparentValue3,
    ]);

    $parentModel = $grandparentModel->children()->create([
        'attribute1' => $parentValue1,
        'attribute2' => $parentValue2,
        'attribute3' => $parentValue3,
    ]);

    $childModel = $parentModel->children()->create();

    $definition = SnapshotDefinition::make()
        ->captureRelations([
            RelationDefinition::from('parent')
                ->capture([
                    'attribute1',
                    'attribute2',
                    'attribute3',
                ])
                ->captureRelations([
                    RelationDefinition::from('parent')
                        ->capture([
                            'attribute1',
                            'attribute2',
                            'attribute3',
                        ])
                ])
        ]);

    $attributes = $collector->getRelatedAttributes($childModel, $definition);

    expect($attributes)
        ->toHaveCount(8)
        ->toHaveKeys([
            'parent_id',
            'parent_attribute1',
            'parent_attribute2',
            'parent_attribute3',
            'parent_parent_id',
            'parent_parent_attribute1',
            'parent_parent_attribute2',
            'parent_parent_attribute3',
        ])
        ->and($attributes['parent_id'])
        ->toBeInstanceOf(RelatedAttributeTransferObject::class)
        ->attribute->toBe('id')
        ->value->toBe(1)
        ->relationPath->toBe(['parent'])
        ->and($attributes['parent_attribute1'])
        ->toBeInstanceOf(RelatedAttributeTransferObject::class)
        ->attribute->toBe('attribute1')
        ->value->toBe($parentValue1)
        ->relationPath->toBe(['parent'])
        ->and($attributes['parent_attribute2'])
        ->toBeInstanceOf(RelatedAttributeTransferObject::class)
        ->attribute->toBe('attribute2')
        ->value->toBe($parentValue2)
        ->relationPath->toBe(['parent'])
        ->and($attributes['parent_attribute3'])
        ->toBeInstanceOf(RelatedAttributeTransferObject::class)
        ->attribute->toBe('attribute3')
        ->value->toBe($parentValue3)
        ->relationPath->toBe(['parent'])
        ->and($attributes['parent_parent_id'])
        ->toBeInstanceOf(RelatedAttributeTransferObject::class)
        ->attribute->toBe('id')
        ->value->toBe(1)
        ->relationPath->toBe(['parent', 'parent'])
        ->and($attributes['parent_parent_attribute1'])
        ->toBeInstanceOf(RelatedAttributeTransferObject::class)
        ->attribute->toBe('attribute1')
        ->value->toBe($grandparentValue1)
        ->relationPath->toBe(['parent', 'parent'])
        ->and($attributes['parent_parent_attribute2'])
        ->toBeInstanceOf(RelatedAttributeTransferObject::class)
        ->attribute->toBe('attribute2')
        ->value->toBe($grandparentValue2)
        ->relationPath->toBe(['parent', 'parent'])
        ->and($attributes['parent_parent_attribute3'])
        ->toBeInstanceOf(RelatedAttributeTransferObject::class)
        ->attribute->toBe('attribute3')
        ->value->toBe($grandparentValue3)
        ->relationPath->toBe(['parent', 'parent']);
});

it('can collect attributes from model, related model and extra attributes', function () {
    $collector = app(AttributeCollectorInterface::class);

    $extraAttribute1 = Str::random(10);
    $extraAttribute2 = Str::random(10);
    $extraAttribute3 = Str::random(10);

    $parentValue1 = Str::random(10);
    $parentValue2 = Str::random(10);
    $parentValue3 = Str::random(10);

    $value1 = Str::random(10);
    $value2 = Str::random(10);
    $value3 = Str::random(10);

    $parentModel = TestParent1Model::query()->create([
        'attribute1' => $parentValue1,
        'attribute2' => $parentValue2,
        'attribute3' => $parentValue3,
    ]);

    $childModel = $parentModel->children()->create([
        'attribute1' => $value1,
        'attribute2' => $value2,
        'attribute3' => $value3,
    ]);

    $definition = SnapshotDefinition::make()
        ->captureAll()
        ->exclude([
            'created_at',
            'castable1',
            'updated_at',
        ])
        ->captureRelations([
            RelationDefinition::from('parent')
                ->captureAll()
                ->exclude([
                    'created_at',
                    'castable1',
                    'updated_at',
                ])
        ]);

    $extraAttributes = [
        'extraAttribute1' => $extraAttribute1,
        'extraAttribute2' => $extraAttribute2,
        'extraAttribute3' => new AttributeTransferObject('extraAttribute3', $extraAttribute3, null),
    ];

    $attributes = $collector->collectAttributes($childModel, $definition, $extraAttributes);

    expect($attributes)
        ->toHaveCount(12)
        ->toHaveKeys([
            'attribute1',
            'attribute2',
            'attribute3',
            'parent_model_id',
            'parent_parent_model_id',
            'parent_attribute1',
            'parent_attribute2',
            'parent_attribute3',
            'parent_id',
            'extraAttribute1',
            'extraAttribute2',
            'extraAttribute3'
        ])
        ->and($attributes['attribute1'])->toBeInstanceOf(AttributeTransferObject::class)
        ->attribute->toBe('attribute1')
        ->value->toBe($value1)
        ->cast->toBeNull()
        ->and($attributes['attribute2'])->toBeInstanceOf(AttributeTransferObject::class)
        ->attribute->toBe('attribute2')
        ->value->toBe($value2)
        ->cast->toBeNull()
        ->and($attributes['attribute3'])->toBeInstanceOf(AttributeTransferObject::class)
        ->attribute->toBe('attribute3')
        ->value->toBe($value3)
        ->cast->toBeNull()
        ->and($attributes['parent_attribute1'])
        ->toBeInstanceOf(RelatedAttributeTransferObject::class)
        ->attribute->toBe('attribute1')
        ->value->toBe($parentValue1)
        ->relationPath->toBe(['parent'])
        ->and($attributes['parent_attribute2'])
        ->toBeInstanceOf(RelatedAttributeTransferObject::class)
        ->attribute->toBe('attribute2')
        ->value->toBe($parentValue2)
        ->relationPath->toBe(['parent'])
        ->and($attributes['parent_attribute3'])->toBeInstanceOf(RelatedAttributeTransferObject::class)
        ->attribute->toBe('attribute3')
        ->value->toBe($parentValue3)
        ->relationPath->toBe(['parent'])
        ->and($attributes['extraAttribute1'])->toBeInstanceOf(AttributeTransferObject::class)
        ->attribute->toBe('extraAttribute1')
        ->value->toBe($extraAttribute1)
        ->cast->toBeNull()
        ->and($attributes['extraAttribute2'])->toBeInstanceOf(AttributeTransferObject::class)
        ->attribute->toBe('extraAttribute2')
        ->value->toBe($extraAttribute2)
        ->cast->toBeNull()
        ->and($attributes['extraAttribute3'])->toBeInstanceOf(AttributeTransferObject::class)
        ->attribute->toBe('extraAttribute3')
        ->value->toBe($extraAttribute3)
        ->cast->toBeNull();
});

test('having valid relation definition but no model associated through the relation doesnt throw an exception', function () {
    $collector = app(AttributeCollectorInterface::class);

    $model = TestRootModel::query()->create();
    $definition = SnapshotDefinition::make()
        ->captureRelations([
            RelationDefinition::from('parent')
                ->captureAll()
        ]);

    $attributes = $collector->getRelatedAttributes($model, $definition);

    expect($collector)
        ->not->toThrow(Exception::class)
        ->and($attributes)->toBeEmpty();
});

it('can exclude timestamps from being captured', function () {
    $collector = app(AttributeCollectorInterface::class);

    $value = Str::random(10);

    $model = TestRootModel::query()->create([
        'attribute1' => $value,
    ]);

    $definition = SnapshotDefinition::make()
        ->captureAll()
        ->excludeTimestamps();

    $attributes = $collector->collectAttributes($model, $definition);

    expect($attributes)
        ->toHaveCount(1)
        ->toHaveKey('attribute1')
        ->and($attributes['attribute1'])->tobeInstanceOf(AttributeTransferObject::class)
        ->attribute->toBe('attribute1')
        ->value->toBe($value)
        ->cast->toBeNull();
});

it('timestamps are prefixed only on root model', function () {
    $collector = app(AttributeCollectorInterface::class);

    $value = Str::random(10);

    $parentModel = TestParent1Model::query()->create();

    $childModel = $parentModel->children()->create();

    $definition = SnapshotDefinition::make()
        ->capture([
            'created_at',
            'updated_at'
        ])
        ->captureRelations([
            RelationDefinition::from('parent')
                ->capture([
                    'created_at',
                    'updated_at'
                ])
        ]);

    $attributes = $collector->collectAttributes($childModel, $definition);

    expect($attributes)
        ->toHaveCount(5)
        ->toHaveKeys([
            'origin_created_at',
            'origin_updated_at',
            'parent_id',
            'parent_created_at',
            'parent_updated_at',
        ]);
});

it('can exlcude casts from being captured', function () {
    $collector = app(AttributeCollectorInterface::class);

    $value1 = Str::random(10);

    $model = TestRootModel::query()->create([
        'castable1' => $value1,
    ]);

    $definition = SnapshotDefinition::make()
        ->captureCasts(false)
        ->capture(['castable1']);

    $attributes = $collector->collectAttributes($model, $definition);

    expect($attributes)
        ->toHaveCount(1)
        ->toHaveKey('castable1')
        ->and($attributes['castable1'])->toBeInstanceOf(AttributeTransferObject::class)
        ->attribute->toBe('castable1')
        ->value->value->toBe($value1)
        ->cast->toBeNull();
});

it('can capture casts from model and related model', function () {
    $collector = app(AttributeCollectorInterface::class);

    $relatedValue = 44454545;
    $childValue = Str::random(10);

    $parentModel = TestParent1Model::query()->create([
        'castable1' => $relatedValue,
    ]);

    $childModel = $parentModel->children()->create([
        'castable1' => $childValue,
    ]);

    $definition = SnapshotDefinition::make()
        ->captureCasts()
        ->capture(['castable1'])
        ->captureRelations([
            RelationDefinition::from('parent')
                ->captureCasts()
                ->capture(['castable1'])
        ]);

    $attributes = $collector->collectAttributes($childModel, $definition);

    expect($attributes)
        ->toHaveCount(3)
        ->toHaveKeys([
            'castable1',
            'parent_castable1'
        ])
        ->and($attributes['castable1'])->toBeInstanceOf(AttributeTransferObject::class)
        ->attribute->toBe('castable1')
        ->value->value->toBe($childValue)
        ->cast->toBe(AsStringable::class)
        ->and($attributes['parent_castable1'])->toBeInstanceOf(RelatedAttributeTransferObject::class)
        ->attribute->toBe('castable1')
        ->value->toBe($relatedValue)
        ->cast->toBe('integer')
        ->and($attributes['parent_id'])->toBeInstanceOf(RelatedAttributeTransferObject::class)
        ->attribute->toBe('id')
        ->value->toBe(1)
        ->cast->toBe('int');
});