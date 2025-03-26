<?php

// Configuration for Laravel Snap package

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
     * Configure the name and timeout used for snapshotting and rewinding operation cache locks.
     *
     * Each model additionally suffixes its table and primary key to ensure lock acquisition does not
     * interfere between different models.
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