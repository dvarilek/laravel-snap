# Laravel Snap

[![Latest Version on Packagist](https://img.shields.io/packagist/v/dvarilek/laravel-snap.svg?style=flat-square)](https://packagist.org/packages/dvarilek/laravel-snap)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/dvarilek/laravel-snap/tests.yml?branch=main&label=Tests)](https://github.com/dvarilek/laravel-snap/actions/workflows/tests.yml)
[![License](https://img.shields.io/github/license/dvarilek/laravel-snap)](https://github.com/dvarilek/laravel-snap/LICENSE.mf)

## Overview
Laravel Snap is a robust and easily configurable package for versioning your application's Eloquent Models by capturing 
their full context in a Snapshot - this even includes attributes from related Models.

Model versions are stored in Snapshots. A Snapshot is a copied state of a Model - state meaning its attributes alongside 
important metadata like their casts. Obviously, since the attributes are copied, no synchronization is done on updates, 
meaning the state gets truly persisted.

A Snapshot can not only store Model attributes from a single Model, but Laravel Snap even handles storing of related Model 
attributes relative to the origin Model. Related attributes are stored directly in the Snapshot - no 'related' Snapshot 
gets created and linked to. This means that the full context of a record gets captured and persisted in a single Snapshot. 
(useful for legislative requirements and auditing purposes)

***
## Installation 
### 1. Install the package:
```bash
composer require dvarilek/laravel-snap
```
### 2. Initialize the package
```bash
php artisan laravel-snap:install
```
Additionally, you can publish the config:
```bash
php artisan vendor:publish --tag=snap-config
```

***
## Getting Started

* Firstly, we need to use Snapshotable trait inside our Model.
The 'getSnapshotDefinition' method specifies, what should and shouldn't get captured in a Snapshot.

```php
use Dvarilek\LaravelSnap\Models\Concerns\Snapshotable;
use Dvarilek\LaravelSnap\ValueObjects\SnapshotDefinition;

class MyModel extends Model
{
    use Snapshotable;        
    
    public static function getSnapshotDefinition(): SnapshotDefinition
    {
        return SnapshotDefinition::make()
            ->captureAll();
    }
    
    // ...
}
```

> [!NOTE]\
> To see all configuration options for SnapshotDefinition, see the [Snapshot Configuration](#snapshot-configuration) section. 

* Then simply call the 'takeSnapshot' method on your Model:

```php
$firstSnapshot = $model->takeSnapshot();

// Access captured Snapshot attribute directly
$name = $firstSnapshot->name;
$email = $firstSnapshot->email;

$model->update([
    'email' => 'different email'
])

$secondSnapshot = $model->takeSnapshot();

$firstSnapshot->email === $secondSnapshot->email; // false
$firstSnapshot->email === $model->email; // false
$secondSnapshot->email === $model->email; // true
```

> [!NOTE]\
> For more information and options, see the [Snapshots](#snapshots) section.

***
## Snapshot Configuration

The configuration of what exactly gets and doesn't get captured in a Snapshot is done fluently through a 
SnapshotDefinition.

### Capturing Model Attributes

Capture all model attributes. Hidden attributes are excluded unless specifically enabled:
```php
SnapshotDefinition::make()
    ->captureAll();
```
\
Capture only specific attributes from the model:
```php
SnapshotDefinition::make()
    ->capture([
        'title',
        'description',
        'status'
    ]);
```

\
By default, hidden attributes are excluded even when specified in the capture method.
To enable capturing hidden attributes you can use:
```php
SnapshotDefinition::make()
    ->captureHiddenAttributes()
```

\
Prevent specified attributes from being included in the Snapshot:
```php
SnapshotDefinition::make()
    ->captureAll()
    ->exclude([
        'password', 
        'remember_token'
    ]);
```

\
By default, attribute casting (like dates, enums, arrays, collections) is preserved in the Snapshot.
To store attributes without their original casting types, you can specifically disable this feature:
```php
SnapshotDefinition::make()
    ->captureCasts(false);
```

\
Exclude Laravel's timestamp fields (created_at, updated_at, deleted_at) from the snapshot:
```php
SnapshotDefinition::make()
    ->captureAll()
    ->excludeTimestamps();
```
> [!IMPORTANT]\
> When timestamps are captured (not excluded), they are prefixed with 'origin_' to prevent conflicts with the
> Snapshot's own timestamps. For example, the model's 'created_at' becomes 'origin_created_at' in the Snapshot.
> The timestamp prefix ('origin_' by default) can be modified in the package's config file.

### Capturing Related Attributes

Capturing attributes from related models stores them in the same Snapshot. The captured attributes 
are prefixed by their relation path relative to the main model. This is done to avoid potential naming 
conflicts between attributes.

To capture related attributes you need to provide a RelationDefinition(s):
```php
use Dvarilek\LaravelSnap\ValueObjects\{SnapshotDefinition, RelationDefinition}

SnapshotDefinition::make()
    ->captureRelations([
        RelationDefinition::from('branch')    
            ->capture([
                'name', 
                'address' 
            ]),     
        RelationDefinition::from('supervisor')    
            ->capture([
                'name' 
            ]),
        // ... Multiple RelationDefinitions can be specified     
    ]);

// Captured attributes in Snapshot
// branch_name
// branch_address
// supervisor_name
```
> [!NOTE]\
> RelationDefinition is a superset of SnapshotDefinition, meaning it has access to all its methods, meaning methods
> like captureAll(), exclude(), excludeTimestamps(), etc. are available.

> [!IMPORTANT]\
> Currently, only BelongsTo relationship is supported. Support for other relationships is planned for future releases.

### Capturing Nested Related Attributes

You can capture deeply nested related attributes by capturing them like this:
The same prefix rules apply.
```php
use Dvarilek\LaravelSnap\ValueObjects\{SnapshotDefinition, RelationDefinition}

SnapshotDefinition::make()
    ->captureRelations([
        RelationDefinition::from('custodian')
            ->capture([
                'name',
                'email'
            ])
            ->captureRelations([
                RelationDefinition::from('department')
                    ->capture([
                        'name', 
                    ]),
                // ...
            ]),
            // ...
    ]);
    
// Captured attributes in snapshot
// custodian_name
// custodian_email
// custodian_department_name
```
<br>

***
## Snapshots

### Working With Snapshots

**Taking Snapshots** \
After using the Snapshotable trait in your Model, you can take Snapshots like this:
```php

$snapshot = $model->takeSnapshot();
```

The 'takeSnapshot' method accepts an array of extra attributes that can be provided at runtime. These attributes are then stored in
the created Snapshot:
```php

// Store the currently authenticated user that created the Snapshot 
$snapshot = $model->takeSnapshot(extraAttributes: [
    'created_by' => auth()->id()
]);
```
> [!TIP]
> The extra attributes can even bypass SnapshotDefinition constraints.

**Retrieving**\
For convenience, the Snapshotable trait adds two relationships for getting the latest and oldest Snapshots out of the box:
```php
  
// Get the most recent snapshot
$latest = $model->latestSnapshot;

// Get the first snapshot ever taken
$oldest = $model->oldestSnapshot;
```

The Snapshot stores its captured attributes in a special JSON column structure. However, you are able to interact with them
directly as if they were regular model attributes with property access:
```php

$snapshot = $model->takeSnapshot();

// Access captured attributes directly
$name = $snapshot->name;
$email = $snapshot->email;
$supervisorName = $snapshot->supervisor_name;
```

> [!NOTE]\
> For more information about how these attributes are stored, see the [Internal Implementation](#internal-implementation) section.

<br>

**Updating** \
Snapshots support both mass assignment and individual property updates:
```php
// Mass assignment
$snapshot->update([
    'name' => 'New Name',
    'email' => 'new.email@example.com'
]);

// Individual property updates
$snapshot->age = 25;
$snapshot->save();
```
<br>

**Querying** \
Because Snapshot attributes are stored in a JSON column, they can't be queried directly as they need to be queried in
the JSON column. For more information about JSON querying see the [**Official Laravel Documentation**](https://laravel.com/docs/10.x/queries#json-where-clauses)

```php
use Dvarilek\LaravelSnap\Models\Snapshot;

// For captured attribute by the name 'custodian_name'
Snapshot::query()->where('storage->custodian_name->value', $custodianName);
```

### Reverting & Versioning
**Basic Reverting**\
Reverting allows you to restore a Model's state to one of its previous snapshots:

```php
// Get the most recent snapshot
$snapshot = $model->latestSnapshot;

// Revert the model to that snapshot
$model = $model->revertTo($snapshot);
```

The sync method on Snapshot model is a convenient shortcut to synchronize the Snapshot's state with its origin model.
In other words, the code above can be replaced with this:
```php
$model = $model->latestSnapshot->sync();
```

Currently, there is no way to configure what exactly gets and doesn't get reverted from a Snapshot like there is for 
taking Snapshots with SnapshotDefinition. However, you can optionally configure if related model attributes should be 
also restored from the Snapshot.
```php
// Revert the model and attributes of related models from the Snapshot (true by default)
$model = $model->revertTo($snapshot, shouldRestoreRelatedAttributes: true);
```

**Versioning**\
To enable full versioning capabilities, your Snapshotable model can optionally include a column that tracks 
the model's current version. This column is optional but required for version-based operations like 'rewind' 
and 'forward'.

If you don't want to manually create and migrate the migration, you can do so by running:
```bash
php artisan laravel-snap:make-versionable "App\Models\YourModel"
```
The command automatically generates a migration for your model and, if confirmed, migrates it.

> [!NOTE]\
> If you run this command without specifying a model, or if the specified model is invalid, the command
> will attempt to look for valid models and prompt you for selection.

Once versioning is enabled, you can navigate through the Model's history by steps.
The 'rewind' method allows you to move backward through your model's history by a specified number of steps:
```php
// Rewind the model by one step backwards (default)
$model->rewind();

// Rewind the model by three steps backwards
$model->rewind(3);
```

Similar to 'rewind', the 'forward' method allows you to move forward through your model's history:
```php
// Move forward by one step (default)
$model->forward();

// Move forward by two steps
$model->forward(2);
```

Both 'forward' and 'rewind' methods call the 'revertTo' method internally and accept the following
optional arguments:
```php
// Default values: 
$model->rewind(shouldDefaultToNearest: false, shouldRestoreRelatedAttributes: true);

$model->forward(shouldDefaultToNearest: false, shouldRestoreRelatedAttributes: true);
```
* **shouldDefaultToNearest**:\
  When set to true, this allows you to revert to the closest snapshot in the direction given
  by the operation type if no exact match is found. 
  For example, if you're rewinding 3 steps but only snapshots for steps 2 and 4 exist, it will use the snapshot at step 2 
  (the nearest available in the rewind direction).
* **shouldRestoreRelatedAttributes**:\ 
  When set to true, the operation will also restore attributes of related models - same as with 'revertTo' method.


### Race Conditions
Concurrent snapshotting and reverting operations by different users can lead to inconsistent behavior.
Laravel Snap prevents these race conditions by using Laravel's atomic locks feature. Once a lock is acquired,
it waits until the operation finishes before allowing other processes to proceed.

The cache lock names and timeouts can be configured inside the package's config file under 'concurrency'.


### Event Hooks
While working with snapshots, you can hook into the snapshotting and reverting processes:

```php
use Dvarilek\LaravelSnap\Models\Concerns\Snapshotable;
use Dvarilek\LaravelSnap\ValueObjects\SnapshotDefinition;

class MyModel extends Model
{
    use Snapshotable;        
    
    // ...
    
    public static function booted(): void
    {
        
        // ...
    
        static::snapshotting(function () {
            // Executes before snapshot creation
            // Return false to prevent the snapshot from being created
        });
        
        static::snapshot(function () {
            // Executes after successful snapshot creation        
        });
        
        static::reverting(function () {
            // Executes before model state is reverted
            // Return false to cancel the reverting operation
        });
        
        static::reverted(function () {
            // Executes after model state has been successfully reverted
        });
    }
}
```

> [!IMPORTANT]\
> Both 'rewind' and 'forward' methods dispatch the 'reverting' and 'reverted' events.

***
## Advanced 

### Internal Implementation
**Storage**\
Snapshots are stored in a dedicated table (model_snapshots) and connected to their original models through a polymorphic 
relationship. The captured attributes are kept in a special JSON 'storage' column with in a structured format, 
preserving all of their necessary metadata.
<br>

* **Base Model Attribute Storage Format**\
Regular model attributes are stored in the format below and represented using **AttributeTransferObject** DTO throughout the package.
```JSON
{
  "name": {
    "attribute": "name",
    "value": "David",
    "cast": "null"
  },
  "email": {
    "attribute": "email",
    "value": "david@example.com",
    "cast": "string"
  }
}
```
* **Related Model Attribute Storage Format** \
Related model attributes are kept in the format below and represented using **RelatedAttributeTransferObject** DTO throughout the package.
```JSON
{
  "branch_name": { 
    "attribute": "name",
    "value": "Main Office",
    "cast": "string",
    "relationPath": ["branch"]
  },
  "branch_manager_email": {
    "attribute": "email",
    "value": "manager@example.com",
    "cast": "string",
    "relationPath": ["branch", "manager"]
  }
}
```
> [!NOTE]\
> relationPath is an array of ordered relationship names relative to the base model.

<br>

**Data Manipulation** \
Working with JSON-encoded data directly through the Snapshot model is possible by hooking into the Model's event handling.

Attributes are decoded from the storage column and set as the model's attributes, subsequent updates to these attributes
are then persisted by encoding them back into the storage column's JSON.
The internal implementation of attributes encoding and decoding is partially derived from [**VirtualColumn Laravel package**](
https://github.com/archtechx/virtualcolumn)

### Advanced Usage
**DTO Usage**\
Internally, all Snapshot attributes are set using the mentioned special DTO's. This is useful to know when you want 
to update Snapshots by appending them with extra attributes or by modifying their existing attributes if you for example 
wish to change their casts.

```php
use Dvarilek\LaravelSnap\DTO\{AttributeTransferObject, RelatedAttributeTransferObject}
use Illuminate\Database\Eloquent\Casts\{AsStringable, AsCollection};

$snapshot->update([
    // Base model attribute with custom casting
    'name' => new AttributeTransferObject(
        attribute: 'name',
        value: 'some different value',
        cast: AsStringable::class
    ),
    
    // Related model attribute with collection casting
    'contract_files' => new RelatedAttributeTransferObject(
        attribute: 'files',
        value: ['path/to/file1'. 'path/to/file2'],
        cast: AsCollection::class,
        relationPath:  => ['department']
    )
]);
```

Additionally, extra attribute information can also be provided when creating a Snapshot with extra attributes.

```php
// Create a Snapshot with invoices as an extra attribute
$snapshot = $model->takeSnapshot(extraAttributes: [
    'invoices' => new AttributeTransferObject(
        attribute: 'invoices',
        value: ['path/to/file1'. 'path/to/file2'],
        cast: 'array'
    ),
]);
```
***


## Testing

```bash
composer test-coverage && composer stan
```
***

## Changelog
Please refer to [Package Releases](https://github.com/dvarilek/laravel-snap/releases) for more information about changes.
## License
This package is under the MIT License. Please refer to [License File](LICENSE.md) for more information