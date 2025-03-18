# Laravel Complete Model Snapshot

> [!CAUTION]
> This package is currently in early stages of active development and should not be used in production environments.

## Overview
This Laravel package allows you to capture, persist and track Eloquent models and their related attributes over time. 
This is achieved using Snapshots. A snapshot is essentially a copied version of a model's state (its attributes), 
that is stored within a dedicated separate snapshot table. For more information see the **Internal Implementation** section.

Any model attributes can be captured. What attributes get and don't get captured in the Snapshot is **fully configurable** 
by the developer.

A standout feature of this package is its ability to also capture specific attributes from related models. Rather than simply
storing a foreign key reference, snapshots preserve the actual attribute values of related models inside of them, ensuring that 
the full context of a record can be captured and persisted, making it particularly useful with storing sensitive records
that need to comply with legislative/regulatory requirements and auditing purposes where an accurate historical representation is 
essential.

***
## Installation 
### 1. Install the package:
```bash
composer require dvarilek/laravel-complete-model-snapshot
```
### 2. Initialize the package
```bash
php artisan complete-model-snapshot:install
```
***
## Basic Usage & Configuration  

Firstly, we need to make our Eloquent model snapshotable and configure what should and shouldn't get captured in our snapshot.
Start by using the Snapshotable trait in your Model:
```php
use Dvarilek\CompleteModelSnapshot\Models\Concerns\Snapshotable;
use Dvarilek\CompleteModelSnapshot\ValueObjects\SnapshotDefinition;

class MyModel extends Model
{
    use Snapshotable;        
    
    public static function getSnapshotDefinition(): SnapshotDefinition
    {
        return SnapshotDefinition::make()
            ->captureAll();
    }
}
```

### Capturing Model Attributes

Capture all model attributes. Hidden attributes are excluded unless specifically enabled:
```php
SnapshotDefinition::make()
    ->captureAll();
```
\
Capture only the specified attributes from the model:
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
Prevent specified attributes from being included in the snapshot:
```php
SnapshotDefinition::make()
    ->captureAll()
    ->exclude([
        'password', 
        'remember_token'
    ]);
```

\
By default, attribute casting (like dates, enums, arrays, collections) is preserved in the snapshot.
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
> snapshot model's own timestamps. For example, the model's 'created_at' becomes 'origin_created_at' in the snapshot.

You can customize the timestamp prefix ('origin_') by publishing and modifying the package's configuration file:
```bash
php artisan vendor:publish --tag=complete-model-snapshot-config
```

### Capturing Related Attributes

Capturing attributes from related models stores them in the same snapshot with the attributes 
being prefixed by their relation path relative to the main model. This is done to avoid potential naming conflicts.

To capture related attributes you need to provide a RelationDefinition(s):
```php
use Dvarilek\CompleteModelSnapshot\ValueObjects\{SnapshotDefinition, RelationDefinition}

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

// Captured attributes in snapshot
// branch_name
// branch_address
// supervisor_name
```
> [!NOTE]\
> RelationDefinition is a superset of SnapshotDefinition, meaning it has access to all of its methods, meaning methods
> like captureAll(), exclude(), excludeTimestamps(), etc. are available.

> [!IMPORTANT]\
> Currently, only BelongsTo relationship is supported. Support for other relationships is planned for future releases.

### Capturing Nested Related Attributes

For deeply nested related attributes the same prefix rules apply. You can capture them
by capturing them like this:
```php
use Dvarilek\CompleteModelSnapshot\ValueObjects\{SnapshotDefinition, RelationDefinition}

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

## Taking Snapshots

A single model can have multiple snapshots. To create new snapshot call the takeSnapshot method on your Snapshotable model.

```php
use Dvarilek\CompleteModelSnapshot\Models\Snapshot;
use Illuminate\Database\Eloquent\Model;

/** @var Snapshot&Model $snapshot */
$snapshot = $model->takeSnapshot();
```

You can include extra attributes when taking a snapshot even whey they are not the actual model attributes.
```php

/** @var Snapshot&Model $snapshot */
$snapshot = $model->takeSnapshot([
    'created_by' => auth()->id()
]);
```
> [!TIP]
> These additional attributes can bypass the SnapshotDefinition rules - meaning they can be captured even if excluded in the definition:

\
For convenience, the Snapshotable trait adds two relationships for getting the latest and oldest snapshot out of the box. 
```php
  
// Get the most recent snapshot
$latest = $model->latestSnapshot;

// Get the first snapshot ever taken
$oldest = $model->oldestSnapshot;
```

\
***
## Working With Snapshots
The snapshot stores its captured attributes in a special JSON column structure. However, you are able to interact with them
as if they were regular model attributes.

**Retrieving** \
Access captured attributes directly as model properties:
```php

$snapshot = $model->takeSnapshot();

// Access captured attributes directly
$name = $snapshot->name;
$email = $snapshot->email;
```
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

#### Querying
Since snapshot attributes are stored in a json column, they can't be queried directly. They need to be queried in
the JSON column. For more information about JSON querying see the [**Official Laravel Documentation**](https://laravel.com/docs/10.x/queries#json-where-clauses)

```php
use Dvarilek\CompleteModelSnapshot\Models\Snapshot;

// For captured attribute by the name 'custodian_name'
Snapshot::query()->where('storage->custodian_name->value', $value);
```
<br>

***
## Rewinding
A model's state can be easily rewound to a previous snapshot. 
Rewinding is done using the rewindTo method that accepts the specific snapshot and an optional parameter that 
determines whether the rewinding should affect related models that have their attribute stored in the snapshot.

```php
$snapshot = $model->latestSnapshot;

// Returns the same model with rewound attributes
$model = $model->rewindTo($snapshot, shouldRestoreRelatedAttributes: true); // shouldRestoreRelatedAttributes is set to true by default
```

The sync method is a convenient shortcut that sets the snapshot state to its origin. Instead of the code above you can do:
```php
$model = $model->latestSnapshot->sync();
```

> [!NOTE]\
> Currently, there is no way to restore only specific attributes using some RestorationDefinition equivalent to SnapshotDefinition


***
## Event Hooks
While working with snapshots, you can hook into the snapshotting and rewinding processes.

```php
use Dvarilek\CompleteModelSnapshot\Models\Concerns\Snapshotable;
use Dvarilek\CompleteModelSnapshot\ValueObjects\SnapshotDefinition;

class MyModel extends Model
{
    use Snapshotable;        
    
    // ...
    
    public static function booted(): void
    {
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
#### Storage
Snapshots are stored in a dedicated table (model_snapshots) and connected to their original models through a polymorphic relationship. 
Their attributes are kept in a dedicated JSON 'storage' column with a structured format that preserves all of their necessary metadata.

<br>

* **Base Model Attribute Storage Format**
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
* **Related Model Attribute Storage Format**
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
> relationPath is an array of ordered relationship names relative to the base model. This is kept for tracking purposes.

<br>

#### Data Manipulation
Working with JSON-encoded data directly on the snapshot model is possible by hooking into the model's event handling.

Attributes are decoded from the storage column and set as the model's attributes, 
subsequent updates to these attributes are persisted by encoding them back into the storage column's JSON.
The internal implementation of attribute encoding and decoding is partially derived from [**VirtualColumn Laravel package**](
https://github.com/archtechx/virtualcolumn)

### Advanced Usage
#### DTO Usage
Internally, all snapshot attribute sare set using special DTO's. These DTO's can also be used to set attributes
with extra metadata e.g. setting/changing casts etc. 

```php
use Dvarilek\CompleteModelSnapshot\DTO\{AttributeTransferObject, RelatedAttributeTransferObject}
use Illuminate\Database\Eloquent\Casts\{AsStringable, AsCollection};

$snapshot->update([
    // Base model attribute with custom casting
    'name' => new AttributeTransferObject(
        'attribute' => 'name',
        'value' => 'A different name',
        'cast' => AsStringable::class
    ),
    
    // Related model attribute with collection casting
    'contract_files' => new RelatedAttributeTransferObject(
        'attribute' => 'files',
        'value' => ['path/to/file1'. 'path/to/file2'],
        'cast' => AsCollection::class,
        'relationPath' => ['department']
    )
]);

// Create a snapshot with extra attribute with a specific cast
$model->takeSnapshot([
    'name' => new AttributeTransferObject(
        'attribute' => 'name',
        'value' => 'David',
        'cast' => AsStringable::class
    ),
]);

```

***
## Testing

```bash
composer test && composer stan
```

## Changelog
Please refer to [Package Releases](https://github.com/dvarilek/laravel-complete-model-snapshot/releases) for more information about changes.

## License
This package is under the MIT License. Please refer to [License File](LICENSE.md) for more information