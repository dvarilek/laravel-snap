<?php

use Dvarilek\LaravelSnap\LaravelSnapServiceProvider;
use Dvarilek\LaravelSnap\Exceptions\InvalidConfigurationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Dvarilek\LaravelSnap\Models\Snapshot;

test('Snapshot Model must be subtype of Model', function () {
    config()->set('laravel-snap.snapshot-model.model', Str::class);

    expect(fn () => LaravelSnapServiceProvider::determineSnapshotModel())
        ->toThrow(InvalidConfigurationException::class);
});

test('Snapshot Model must implement SnapshotContract interface', function () {
    config()->set('laravel-snap.snapshot-model.model', Model::class);

    expect(fn () => LaravelSnapServiceProvider::determineSnapshotModel())
        ->toThrow(InvalidConfigurationException::class);
});

test('determineSnapshotModel accepts Snapshot Model', function () {
    config()->set('laravel-snap.snapshot-model.model', Snapshot::class);

    expect(fn () => LaravelSnapServiceProvider::determineSnapshotModel())
        ->not->toThrow(InvalidConfigurationException::class);
});