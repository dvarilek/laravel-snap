<?php

declare(strict_types=1);

use Dvarilek\CompleteModelSnapshot\Services\Contracts\AttributeRestorerInterface;
use Dvarilek\CompleteModelSnapshot\ValueObjects\{SnapshotDefinition, RelationDefinition};
use Dvarilek\CompleteModelSnapshot\Tests\Models\{TestRootModel, TestParent1Model, TestAnotherParent1Model, TestParent2Model};
use Dvarilek\CompleteModelSnapshot\Services\SnapshotAttributeRestorer;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Illuminate\Support\Stringable;

test('default attribute restorer is an instance of SnapshotAttributeRestorer', function () {
     $restorer = app(AttributeRestorerInterface::class);

     expect($restorer)->toBeInstanceOf(SnapshotAttributeRestorer::class);
});

test('attribute restorer can restore model attributes by rewinding to a specific snapshot', function () {
    $restorer = app(AttributeRestorerInterface::class);

    $firstSnapshotValue1 = "first1";
    $firstSnapshotValue2 = "first2";
    $firstSnapshotValue3 = "first3";

    $secondSnapshotValue1 = "second1";
    $secondSnapshotValue2 = "second2";

    $model = TestRootModel::query()->create([
        'attribute1' => $firstSnapshotValue1,
        'attribute2' => $firstSnapshotValue2,
        'attribute3' => $firstSnapshotValue3,
    ]);

    $firstSnapshot = $model->takeSnapshot();

    $model->update([
        'attribute1' => $secondSnapshotValue1,
        'attribute2' => $secondSnapshotValue2,
    ]);

    $secondSnapshot = $model->refresh()->takeSnapshot();
    $model = $restorer->rewindTo($model, $firstSnapshot);

    expect($model->refresh())
        ->toBeInstanceOf(TestRootModel::class)
        ->attribute1->toBe($firstSnapshotValue1)
        ->attribute2->toBe($firstSnapshotValue2)
        ->attribute3->toBe($firstSnapshotValue3);

    $model = $restorer->rewindTo($model, $secondSnapshot);

    expect($model->refresh())
        ->toBeInstanceOf(TestRootModel::class)
        ->attribute1->toBe($secondSnapshotValue1)
        ->attribute2->toBe($secondSnapshotValue2)
        ->attribute3->toBe($firstSnapshotValue3);
});

test('attribute restorer can restore castable model attributes by rewinding to a specific snapshot', function () {
    $restorer = app(AttributeRestorerInterface::class);

    $firstSnapshotValue1 = Str::of('firstSnapshot1');
    $firstSnapshotValue2 = ['firstSnapshot2'];
    $firstSnapshotValue3 = null;

    $secondSnapshotValue1 = Str::of('secondSnapshot1');
    $secondSnapshotValue3 = "secondSnapshot3";

    $mainModel = new class extends TestRootModel {

        protected $casts = [
            'attribute1' => AsStringable::class,
            'attribute2' => 'array'
        ];

        public static function getSnapshotDefinition(): SnapshotDefinition
        {
            return SnapshotDefinition::make()
                ->captureAll();
        }
    };

    $mainModel = $mainModel::query()->create([
        'attribute1' => $firstSnapshotValue1,
        'attribute2' => $firstSnapshotValue2,
        'attribute3' => $firstSnapshotValue3,
    ]);

    $firstSnapshot = $mainModel->takeSnapshot();

    $mainModel->update([
        'attribute1' => $secondSnapshotValue1,
        'attribute3' => $secondSnapshotValue3,
    ]);

    $secondSnapshot = $mainModel->takeSnapshot();

    $mainModel = $restorer->rewindTo($mainModel, $firstSnapshot);

    expect($mainModel)
        ->toBeInstanceOf(TestRootModel::class)
        ->attribute1->toBeInstanceOf(Stringable::class)
        ->attribute1->value->toBe($firstSnapshotValue1->value)
        ->attribute2->toBe($firstSnapshotValue2)
        ->attribute3->toBe($firstSnapshotValue3);

    $mainModel = $restorer->rewindTo($mainModel, $secondSnapshot);

    expect($mainModel)
        ->toBeInstanceOf(TestRootModel::class)
        ->attribute1->toBeInstanceOf(Stringable::class)
        ->attribute1->value->toBe($secondSnapshotValue1->value)
        ->attribute2->toBe($firstSnapshotValue2)
        ->attribute3->toBe($secondSnapshotValue3);
});

test('attribute restorer can restore related model attributes by rewinding to a specific snapshot', function () {
    $restorer = app(AttributeRestorerInterface::class);

    $firstSnapshotParentValue1 = 'firstSnapshotParent1';
    $firstSnapshotParentValue2 = 'firstSnapshotParent2';
    $firstSnapshotAnotherParentValue1 = 'firstSnapshotAnotherParent1';
    $firstSnapshotAnotherParentValue2 = 'firstSnapshotAnotherParent2';

    $secondSnapshotParentValue1 = 'secondSnapshotParent1';
    $secondSnapshotParentValue2 = 'secondSnapshotParent2';
    $secondSnapshotAnotherParentValue1 = 'firstSnapshotAnotherParent1';

    $mainModel = new class extends TestRootModel {
        public static function getSnapshotDefinition(): SnapshotDefinition
        {
            return SnapshotDefinition::make()
                ->captureAll()
                ->captureRelations([
                    RelationDefinition::from('parent')
                        ->capture([
                            'attribute1',
                            'attribute2'
                        ]),
                    RelationDefinition::from('anotherParent')
                        ->capture([
                            'attribute1',
                            'attribute2'
                        ]),
                ]);
        }
    };

    $parentModel = TestParent1Model::query()->create([
        'attribute1' => $firstSnapshotParentValue1,
        'attribute2' => $firstSnapshotParentValue2,
    ]);

    $anotherParentModel = TestAnotherParent1Model::query()->create([
        'attribute1' => $firstSnapshotAnotherParentValue1,
        'attribute2' => $firstSnapshotAnotherParentValue2,
    ]);

    $mainModel = $mainModel::query()->create([
        'parent_model_id' => $parentModel->getKey(),
        'another_parent_model_id' => $anotherParentModel->getKey(),
    ]);

    $firstSnapshot = $mainModel->takeSnapshot();

    $parentModel->update([
        'attribute1' => $secondSnapshotParentValue1,
        'attribute2' => $secondSnapshotParentValue2,
    ]);

    $anotherParentModel->update([
        'attribute1' => $secondSnapshotAnotherParentValue1
    ]);

    $secondSnapshot = $mainModel->refresh()->takeSnapshot();

    $mainModel = $restorer->rewindTo($mainModel, $firstSnapshot);

    expect($mainModel)
        ->toBeInstanceOf(TestRootModel::class)
        ->and($mainModel->parent)
        ->attribute1->toBe($firstSnapshotParentValue1)
        ->attribute2->toBe($firstSnapshotParentValue2)
        ->and($mainModel->anotherParent)
        ->attribute1->toBe($firstSnapshotAnotherParentValue1)
        ->attribute2->toBe($firstSnapshotAnotherParentValue2);

    $mainModel = $restorer->rewindTo($mainModel, $secondSnapshot);

    expect($mainModel)
        ->toBeInstanceOf(TestRootModel::class)
        ->and($mainModel->parent)
        ->attribute1->toBe($secondSnapshotParentValue1)
        ->attribute2->toBe($secondSnapshotParentValue2)
        ->and($mainModel->anotherParent)
        ->attribute1->toBe($secondSnapshotAnotherParentValue1)
        ->attribute2->toBe($firstSnapshotAnotherParentValue2);
});

test('attribute restorer can restore nested related model attributes by rewinding to a specific snapshot', function () {
    $restorer = app(AttributeRestorerInterface::class);

    $firstSnapshotParentValue1 = 'firstSnapshotParent1';
    $firstSnapshotParentValue2 = 'firstSnapshotParent2';
    $firstSnapshotParentParentValue1 = 'firstSnapshotParentParent1';
    $firstSnapshotParentParentValue2 = 'firstSnapshotParentParent2';

    $secondSnapshotParentValue1 = 'secondSnapshotParent1';
    $secondSnapshotParentValue2 = 'secondSnapshotParent2';
    $secondSnapshotParentParentValue1 = 'secondSnapshotParentParent1';

    $mainModel = new class extends TestRootModel {
        public static function getSnapshotDefinition(): SnapshotDefinition
        {
            return SnapshotDefinition::make()
                ->captureAll()
                ->captureRelations([
                    RelationDefinition::from('parent')
                        ->capture([
                            'attribute1',
                            'attribute2'
                        ])
                        ->captureRelations([
                            RelationDefinition::from('parent')
                                ->capture([
                                    'attribute1',
                                    'attribute2'
                                ])
                        ]),
                ]);
        }
    };

    $parentParentModel = TestParent2Model::query()->create([
        'attribute1' => $firstSnapshotParentParentValue1,
        'attribute2' => $firstSnapshotParentParentValue2,
    ]);

    $parentModel = TestParent1Model::query()->create([
        'attribute1' => $firstSnapshotParentValue1,
        'attribute2' => $firstSnapshotParentValue2,
        'parent_model_id' => $parentParentModel->getKey(),
    ]);

    $mainModel = $mainModel::query()->create([
        'parent_model_id' => $parentModel->getKey(),
    ]);

    $firstSnapshot = $mainModel->takeSnapshot();

    $parentModel->update([
        'attribute1' => $secondSnapshotParentValue1,
        'attribute2' => $secondSnapshotParentValue2,
    ]);

    $parentParentModel->update([
        'attribute1' => $secondSnapshotParentParentValue1,
    ]);

    $secondSnapshot = $mainModel->refresh()->takeSnapshot();

    $mainModel = $restorer->rewindTo($mainModel, $firstSnapshot);

    expect($mainModel)
        ->toBeInstanceOf(TestRootModel::class)
        ->and($mainModel->parent)
        ->attribute1->toBe($firstSnapshotParentValue1)
        ->attribute2->toBe($firstSnapshotParentValue2)
        ->and($mainModel->parent->parent)
        ->attribute1->toBe($firstSnapshotParentParentValue1)
        ->attribute2->toBe($firstSnapshotParentParentValue2);

    $mainModel = $restorer->rewindTo($mainModel, $secondSnapshot);

    expect($mainModel)
        ->toBeInstanceOf(TestRootModel::class)
        ->and($mainModel->parent)
        ->attribute1->toBe($secondSnapshotParentValue1)
        ->attribute2->toBe($secondSnapshotParentValue2)
        ->and($mainModel->parent->parent)
        ->attribute1->toBe($secondSnapshotParentParentValue1)
        ->attribute2->toBe($firstSnapshotParentParentValue2);
});