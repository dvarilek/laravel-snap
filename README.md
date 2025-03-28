# Laravel Snap

> [!CAUTION]
> This package is currently in early stages of active development and should not be used in production environments.

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
php artisan vendor:publish --tag=laravel-snap-config
```

***
## Starting

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

### Rewinding
A model's state can be easily restored from its previously taken Snapshot.
Call the rewindTo method and provide the Snapshot you wish to rewind to:
```php
$snapshot = $model->latestSnapshot;

// Returns the same model with rewound attributes
$model = $model->rewindTo($snapshot, shouldRestoreRelatedAttributes: true); 
```

> [!NOTE]\
> You can specify if you want to also restore attributes that originate from related models.

<br/>

The sync method is a convenient shortcut that sets the Snapshot's state to its origin. 
Instead of the code above you can by directly calling on Snapshot instance do:
```php
$model = $model->latestSnapshot->sync();
```

> [!NOTE]\
> Currently, there is no way to restore only specific attributes using some RestorationDefinition equivalent to SnapshotDefinition

### Race Conditions
Concurrent snapshotting and rewinding operations by different users can lead to inconsistent behavior.
Laravel Snap prevents these race conditions by using Laravel's atomic locks feature. Once a lock is acquired,
it waits until the operation finishes before allowing other processes to proceed.

The cache lock names and timeouts can be configured inside the package's config file under 'concurrency'.


### Event Hooks
While working with snapshots, you can hook into the snapshotting and rewinding processes:

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
        
        static::rewinding(function () {
            // Executes before model state is rewound
            // Return false to cancel the rewinding operation
        });
        
        static::rewound(function () {
            // Executes after model state has been successfully rewound
        });
    }
}
```

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
composer test && composer stan
```
***

## Changelog
Please refer to [Package Releases](https://github.com/dvarilek/laravel-snap/releases) for more information about changes.
## License
This package is under the MIT License. Please refer to [License File](LICENSE.md) for more information