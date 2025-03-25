<?php

// Configuration file for Laravel Snap package

use Dvarilek\LaravelSnap\Models\Snapshot;

return [

    /**
     * Configuration for the polymorphic relationships between your models and their snapshots.
     *
     * model      - The Snapshot model class to use for storing model snapshots
     *            - must implement @see \Dvarilek\LaravelSnap\Models\Contracts\SnapshotContract
     * morph-name - The base name used for polymorphic relations
     * morph-type - The database column storing the parent model's class name
     * morph-id   - The database column storing the parent model's ID
     * local-key  - The primary key of your models (typically 'id')
     *
     * @see \Dvarilek\LaravelSnap\Models\Concerns\Snapshotable trait for implementation details
     */
    'snapshot-model' => [

        'model' => Snapshot::class,

        'morph-name' => 'origin',

        'morph-type' => 'origin_type',

        'morph-id' => 'origin_id',

        'local-key' => 'id',

    ],

    /**
     * The prefix added to timestamp attributes when creating a snapshot.
     *
     * For example, the model's 'created_at' becomes 'origin_created_at' in the snapshot.
     *
     * Changing this value is not recommended in production as it could lead to problems with
     * data consistency within snapshots.
     */
    'timestamp-prefix' => 'origin_',

    /**
     * Configure the name and timeout used for locks in snapshotting and rewinding operations.
     *
     * Models using the Snapshotable trait will append their table name and primary key
     * to the specified lock name.This makes sure that lock acquisition is done per model instance
     * rather than per model class.
     */
    'concurrency' => [

        'snapshotting-lock' => [

            'name' => 'snapshotting_atomic_lock',

            'timeout' => 10

        ],

        'rewinding-lock' => [

            'name' => 'rewinding_atomic_lock',

            'timeout' => 10

        ]

    ]
];