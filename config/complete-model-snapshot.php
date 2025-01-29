<?php

use Dvarilek\CompleteModelSnapshot\Models\Snapshot;
use Dvarilek\CompleteModelSnapshot\Models\Concerns\Snapshotable;

return [

    /**
     * Configuration for the polymorphic relationships between your models and their snapshots.
     *
     * model      - The Snapshot model class to use for storing model snapshots
     * morph-name - The base name used for polymorphic relations
     * morph-type - The database column storing the parent model's class name
     * morph-id   - The database column storing the parent model's ID
     * local-key  - The primary key of your models (typically 'id')
     *
     * @see Snapshotable trait for implementation details
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
     'timestamp-prefix' => 'origin_'

];