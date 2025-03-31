<?php

declare(strict_types=1);

use Dvarilek\LaravelSnap\Exceptions\InvalidSnapshotException;
use Dvarilek\LaravelSnap\Exceptions\SnapshotableModelException;
use Dvarilek\LaravelSnap\Tests\Models\TestRootModel;
use Dvarilek\LaravelSnap\DTO\AttributeTransferObject;
use Dvarilek\LaravelSnap\Tests\Models\TestVersionableModel;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Casts\AsStringable;
use Dvarilek\LaravelSnap\Models\Snapshot;
use Illuminate\Support\Facades\DB;
use Dvarilek\LaravelSnap\Models\Contracts\SnapshotContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Stringable;
use Illuminate\Support\Str;
use Carbon\Carbon;

it('can make snapshot', function () {
    $value1 = Str::random(10);
    $value2 = Str::random(11);

    $extraValue1 = Str::random(12);
    $extraValue2 = Str::random(13);

    $model = TestRootModel::query()->create([
        'attribute1' => $value1,
        'attribute2' => $value2,
    ]);

    $snapshot = $model->takeSnapshot([
        'extraAttribute1' => $extraValue1,
        'extraAttribute2' => new AttributeTransferObject('extraAttribute2', $extraValue2, AsStringable::class),
    ]);

    $rawRecordData = DB::table($snapshot->getTable())->first();

    expect($snapshot)->toBeInstanceOf(Snapshot::class)
        ->attribute1->toBe($value1)
        ->attribute2->toBe($value2)
        ->version->toBe(1)
        ->extraAttribute1->toBe($extraValue1)
        ->extraAttribute2->toBeInstanceOf(Stringable::class)
        ->extraAttribute2->value->toBe($extraValue2)
        ->and($rawRecordData->storage)->toBeJson()
        ->and($rawRecordData)->not->toHaveProperty('attribute1')
        ->and($rawRecordData)->not->toHaveProperty('attribute2')
        ->and($rawRecordData)->not->toHaveProperty('extraAttribute1')
        ->and($rawRecordData)->not->toHaveProperty('extraAttribute2')
        ->and(json_decode($rawRecordData->storage, true))
        ->toHaveKeys([
            'attribute1',
            'attribute2',
            'extraAttribute1',
            'extraAttribute2',
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
        ])
        ->and(json_decode($rawRecordData->storage, true)['extraAttribute1'])
        ->toBe([
            'attribute' => 'extraAttribute1',
            'value' => $extraValue1,
            'cast' => null,
        ])
        ->and(json_decode($rawRecordData->storage, true)['extraAttribute2'])
        ->toBe([
            'attribute' => 'extraAttribute2',
            'value' => $extraValue2,
            'cast' => AsStringable::class,
        ]);
});

describe("Versioning", function () {

    test('latestSnapshot method returns the latest snapshot ', function () {
        /* @var TestRootModel $model */
        $model = TestRootModel::query()->create();

        $model->takeSnapshot();
        $model->takeSnapshot();
        $latestSnapshot = $model->takeSnapshot();

        expect($model->snapshots)
            ->toHaveCount(3)
            ->and($model->latestSnapshot->getKey())->toBe($latestSnapshot->getKey());
    });

    test('oldestSnapshot method returns the oldest snapshot ', function () {
        /* @var TestRootModel $model */
        $model = TestRootModel::query()->create();

        $oldestSnapshot = $model->takeSnapshot();
        $model->takeSnapshot();
        $model->takeSnapshot();

        expect($model->snapshots)
            ->toHaveCount(3)
            ->and($model->oldestSnapshot->getKey())->toBe($oldestSnapshot->getKey());
    });

    test('revertTo method accepts only valid snapshots', function () {

        $model = TestRootModel::query()->create();
        $differentModel = TestRootModel::query()->create();

        $snapshot = $model->takeSnapshot();

        expect(fn () => $differentModel->revertTo($snapshot))
            ->toThrow(InvalidSnapshotException::class);
    });

    test('sync Snapshot method synchronizes origin with the Snapshot Model', function () {
        $snapshotValue1 = "snapshotValue1";
        $snapshotValue2 = "snapshotValue2";

        $model = TestRootModel::query()->create([
            'attribute1' => $snapshotValue1,
            'attribute2' => $snapshotValue2,
        ]);

        $snapshot = $model->takeSnapshot();

        $model->update([
            'attribute1' => Str::random(10),
            'attribute2' => Str::random(10),
        ]);

        $model = $snapshot->sync();

        expect($model)
            ->toBeInstanceOf(TestRootModel::class)
            ->attribute1->toBe($snapshotValue1)
            ->attribute2->toBe($snapshotValue2);
    });

    test('multiple snapshots of the same model have different versions', function () {
        /* @var TestRootModel $model */
        $model =  TestRootModel::query()->create();

        $firstSnapshot = $model->takeSnapshot();
        $secondSnapshot = $model->takeSnapshot();
        $thirdSnapshot = $model->takeSnapshot();

        expect($firstSnapshot->getVersion())->toBe(1)
            ->and($secondSnapshot->getVersion())->toBe(2)
            ->and($thirdSnapshot->getVersion())->toBe(3);
    });

    test('versionable model updates its current version when taking snapshots', function () {
        /* @var TestVersionableModel $model */
        $model = TestVersionableModel::query()->create();
        $initialVersion = $model->current_version;

        $model->takeSnapshot();
        $versionAfterFirstSnapshot = $model->current_version;

        $model->takeSnapshot();
        $versionAfterSecondSnapshot = $model->current_version;

        $model->takeSnapshot();
        $versionAfterThirdSnapshot = $model->current_version;

        expect($initialVersion)->toBeNull()
            ->and($versionAfterFirstSnapshot)->toBe(1)
            ->and($versionAfterSecondSnapshot)->toBe(2)
            ->and($versionAfterThirdSnapshot)->toBe(3);
    });

    test('rewinding and forwarding by steps throws error on Model with no current version column', function () {
        /* @var TestRootModel $model */
        $model = TestRootModel::query()->create();

        expect(fn () => $model->rewind())
            ->toThrow(SnapshotableModelException::class)
            ->and(fn () => $model->forward())
            ->toThrow(SnapshotableModelException::class);
    });

    test('rewinding and forwarning by zero steps throws an exception', function () {
        /* @var TestVersionableModel $model */
        $model = TestVersionableModel::query()->create();

        expect(fn () => $model->rewind(0))
            ->toThrow(SnapshotableModelException::class)
            ->and(fn () => $model->forward(0))
            ->toThrow(SnapshotableModelException::class);
    });

    test('rewinding and forwarding with no snapshots throws an exception', function () {
        /* @var TestVersionableModel $model */
        $model = TestVersionableModel::query()->create();

        expect(fn () => $model->rewind())
            ->toThrow(InvalidSnapshotException::class)
            ->and(fn () => $model->forward())
            ->toThrow(InvalidSnapshotException::class);
    });

    test('rewinding by a specific number of steps rewinds to the correct snapshot', function () {
        /* @var TestVersionableModel $model */
        $model = TestVersionableModel::query()->create();

        $firstSnapshotValue = 'firstSnapshotValue';
        $secondSnapshotValue = 'secondSnapshotValue';
        $thirdSnapshotValue = 'thirdSnapshotValue';

        $model->update(['attribute1' => $firstSnapshotValue]);
        $model->takeSnapshot();

        $model->update(['attribute1' => $secondSnapshotValue]);
        $model->takeSnapshot();

        $model->update(['attribute1' => $thirdSnapshotValue]);
        $model->takeSnapshot();

        $secondModel = clone $model;
        $model = $model->rewind(2);
        $secondModel->rewind();

        expect($model)
            ->attribute1->toBe($firstSnapshotValue)
            ->current_version->toBe(1)
            ->and($secondModel)
            ->attribute1->toBe($secondSnapshotValue)
            ->current_version->toBe(2);
    });

    test('rewinding by a specific number of steps rewinds to the nearest snapshot if specified', function () {
        /* @var TestVersionableModel $model */
        $model = TestVersionableModel::query()->create();

        $firstSnapshotValue = 'firstSnapshotValue';
        $secondSnapshotValue = 'secondSnapshotValue';
        $thirdSnapshotValue = 'thirdSnapshotValue';

        $model->update(['attribute1' => $firstSnapshotValue]);
        $model->takeSnapshot();

        $model->update(['attribute1' => $secondSnapshotValue]);
        $secondSnapshot = $model->takeSnapshot();
        $secondSnapshot->forceDelete();

        $model->update(['attribute1' => $thirdSnapshotValue]);
        $model->takeSnapshot();

        $model = $model->rewind(shouldDefaultToNearest: true);

        expect($model)
            ->attribute1->toBe($firstSnapshotValue)
            ->current_version->toBe(1);
    });

    test('forwarding by a specific number of steps forwards to the correct snapshot', function () {
        /* @var TestVersionableModel $model */
        $model = TestVersionableModel::query()->create();

        $firstSnapshotValue = 'firstSnapshotValue';
        $secondSnapshotValue = 'secondSnapshotValue';
        $thirdSnapshotValue = 'thirdSnapshotValue';

        $model->update(['attribute1' => $firstSnapshotValue]);
        $model->takeSnapshot();

        $model->update(['attribute1' => $secondSnapshotValue]);
        $model->takeSnapshot();

        $model->update(['attribute1' => $thirdSnapshotValue]);
        $model->takeSnapshot();

        $model = $model->rewind(2);

        expect($model)
            ->attribute1->toBe($firstSnapshotValue)
            ->current_version->toBe(1);

        $secondModel = clone $model;
        $model = $model->forward(2);
        $secondModel->forward();

        expect($model)
            ->attribute1->toBe($thirdSnapshotValue)
            ->current_version->toBe(3)
            ->and($secondModel)
            ->attribute1->toBe($secondSnapshotValue)
            ->current_version->toBe(2);
    });

    test('forwarding by a specific number of steps forwards to the nearest snapshot if specified', function () {
        /* @var TestVersionableModel $model */
        $model = TestVersionableModel::query()->create();

        $firstSnapshotValue = 'firstSnapshotValue';
        $secondSnapshotValue = 'secondSnapshotValue';
        $thirdSnapshotValue = 'thirdSnapshotValue';

        $model->update(['attribute1' => $firstSnapshotValue]);
        $model->takeSnapshot();

        $model->update(['attribute1' => $secondSnapshotValue]);
        $secondSnapshot = $model->takeSnapshot();
        $secondSnapshot->forceDelete();

        $model->update(['attribute1' => $thirdSnapshotValue]);
        $model->takeSnapshot();

        $model = $model->rewind(2);

        expect($model)
            ->attribute1->toBe($firstSnapshotValue)
            ->current_version->toBe(1);

        $model = $model->forward(shouldDefaultToNearest: true);

        expect($model)
            ->attribute1->toBe($thirdSnapshotValue)
            ->current_version->toBe(3);
    });
});

describe("Event Hooks", function () {

    test('takeSnapshot method dispatches events during the snapshot creation process', function () {
        Event::fake();

        $model = TestRootModel::query()->create();

        $model->takeSnapshot();

        Event::assertDispatched('eloquent.snapshotting: ' . $model::class, function (string $eventName, TestRootModel $payloadModel) use ($model) {
            return $payloadModel->is($model);
        });

        Event::assertDispatched('eloquent.snapshot: ' . $model::class, function (string $eventName, TestRootModel $payloadModel) use ($model) {
            return $payloadModel->is($model);
        });
    });

    test('takeSnapshot operation is canceled when false is returned from snapshotting listener', function () {
        $class = new class extends TestRootModel {
            public static function booted(): void
            {
                static::snapshotting(fn () => false);
            }
        };

        $model = $class::query()->create();
        $result = $model->takeSnapshot();

        expect($result)
            ->toBeNull()
            ->and($model->snapshots)
            ->toHaveCount(0);
    });

    test('revertTo method dispatches events during the restoration process', function () {
        Event::fake();

        $model = TestRootModel::query()->create();

        $snapshot = $model->takeSnapshot();
        $model = $model->revertTo($snapshot);

        Event::assertDispatched('eloquent.reverting: ' . $model::class, function (string $eventName, TestRootModel $payloadModel) use ($model) {
            return $payloadModel->is($model);
        });

        Event::assertDispatched('eloquent.reverted: ' . $model::class, function (string $eventName, TestRootModel $payloadModel) use ($model) {
            return $payloadModel->is($model);
        });
    });

    test('revertTo operation is canceled when false is returned from reverting listener', function () {
        $class = new class extends TestRootModel {
            public static function booted(): void
            {
                static::reverting(fn () => false);
            }
        };

        $model = $class::query()->create([
            'attribute1' => 'firstSnapshotValue',
        ]);
        $firstSnapshot = $model->takeSnapshot();

        $model->update([
            'attribute1' => 'secondSnapshotValue',
        ]);
        $model->takeSnapshot();

        $result = $model->revertTo($firstSnapshot);

        expect($result)
            ->toBeNull()
            ->and($model)
            ->attribute1->toBe('secondSnapshotValue');
    });
});

describe('Atomic Locks', function () {
    test('concurrent snapshot creation is prevented', function () {
        /** @var TestRootModel $model */
        $model = TestRootModel::query()->create();

        $lock = Cache::lock(
            config('laravel-snap.concurrency.snapshotting-lock.name') . "_" . $model->getTable() . "_" . $model->getKey(),
            config('laravel-snap.concurrency.snapshotting-lock.timeout')
        );
        $lock->acquire();

        try {
            $result = $model->takeSnapshot();

            expect($result)->toBeFalse();
        } finally {
            $lock->release();
        }
    });

    test('concurrent snapshot reverting is prevented', function () {
        /** @var TestRootModel $model */
        $model = TestRootModel::query()->create();

        $lock = Cache::lock(
            config('laravel-snap.concurrency.reverting-lock.name') . "_" . $model->getTable() . "_" . $model->getKey(),
            config('laravel-snap.concurrency.reverting-lock.timeout')
        );
        $lock->acquire();

        /** @var SnapshotContract&Model $firstSnapshot */
        $firstSnapshot = $model->takeSnapshot();

        try {
            $result = $model->revertTo($firstSnapshot);

            expect($result)->toBeFalse();
        } finally {
            $lock->release();
        }
    });
});

