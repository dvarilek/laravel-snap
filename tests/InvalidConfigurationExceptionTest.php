<?php

use Dvarilek\CompleteModelSnapshot\LaravelCompleteModelSnapshotServiceProvider;
use Dvarilek\CompleteModelSnapshot\Exceptions\InvalidConfigurationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Dvarilek\CompleteModelSnapshot\Models\Snapshot;

test('Snapshot Model must be subtype of Model', function () {
    config()->set('complete-model-snapshot.snapshot-model.model', Str::class);

    expect(fn () => LaravelCompleteModelSnapshotServiceProvider::determineSnapshotModel())
        ->toThrow(InvalidConfigurationException::class);
});

test('Snapshot Model must implement SnapshotContract interface', function () {
    config()->set('complete-model-snapshot.snapshot-model.model', Model::class);

    expect(fn () => LaravelCompleteModelSnapshotServiceProvider::determineSnapshotModel())
        ->toThrow(InvalidConfigurationException::class);
});

test('determineSnapshotModel accepts Snapshot Model', function () {
    config()->set('complete-model-snapshot.snapshot-model.model', Snapshot::class);

    expect(fn () => LaravelCompleteModelSnapshotServiceProvider::determineSnapshotModel())
        ->not->toThrow(InvalidConfigurationException::class);
});