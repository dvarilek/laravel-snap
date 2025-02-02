# Laravel Complete Model Snapshot

Laravel package for capturing and persisting the state of Eloquent models and their relationships.

> [!CAUTION]
> This package is currently in early stages of active development and should not be used in production environments. 

## Overview
This Laravel package allows you to create snapshots of Eloquent models. A snapshot is essentially a **copied state** of a model
that can be thought of as a way of **storing** and persisting different **versions of your Eloquent models**.

What attributes get and don't get captured is **fully configurable** by the developer.

What makes this package truly unique, is that it also allows you to capture specific attributes from **related models**.
However, instead of just storing a foreign key reference, the snapshots **actually contain the related model's attributes**.

This means, that captured related data **remains preserved, regardless of any subsequent changes or deletions** to the original model.

It’s particularly useful when dealing with sensitive records, where having an accurate historical 
state is needed for **compliance with legislative/regulatory requirements** and auditing purposes.

**Examples of Usage**
+ **Legal documents** (preserving related attributes for legislative reasons - such as names, emails, etc...) 
+ **Customer transactions** (transaction details such as customer names, addresses, payment methods etc...)

***
## Installation 
Install the package:
```bash
composer require dvarilek/laravel-complete-model-snapshot
```
Initialize the package
```bash
php artisan install laravel-complete-model-snapshot
```
***
## Basic Usage & Configuration  

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

The Snapshotable trait will require you to implement the **getSnapshotDefinition** method, which returns a SnapshotDefinition 
instance that acts as a fluent interface for configuring exactly what **should and shouldn't be captured** in a snapshot.


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
By default, attribute casting (like dates, enums, arrays, collections) is preserved in the snapshot.
To store attributes without their original casting types, you can specifically disable this feature:
```php
SnapshotDefinition::make()
    ->captureCasts(false);
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
php artisan vendor:publish --tag=laravel-complete-model-snapshot-config
```

### Capturing Related Attributes

You can capture attributes from related models using the captureRelations method. 
Each relation is configured using a RelationDefinition:
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
        // ... Multiple relations can be defined     
    ]);
```
> [!NOTE]\
> RelationDefinition extends SnapshotDefinition, which means all configuration options 
> (like captureAll(), exclude(), excludeTimestamps(), etc.) are available for related models.

> [!IMPORTANT]\
> Currently, only BelongsTo relationships are supported. Support for other relationships is planned for future releases

### Capturing Nested Related Attributes

You can capture attributes from deeply nested relations by capturing them in other RelationDefinitions:
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
```
<br>

> [!IMPORTANT]\
> All captured related attributes will be prefixed by their relationship path relative to the base model to prevent any 
> potential naming conflicts.

Example: 
  * Relationships: Laptop (base model) -> BelongsTo (custodian) -> BelongsTo (department)
  * Captured Attribute: name of department
  * Resulting Attribute Name in Snapshot: **custodian_department_name**
***
## Taking Snapshots

The Snapshotable trait also adds the takeSnapshot method to your Eloquent model.
This method will create a snapshot based on the **model's SnapshotDefinition rules**.

```php
use Dvarilek\CompleteModelSnapshot\Models\Snapshot;

/** @var Snapshot $snapshot */
$snapshot = $model->takeSnapshot();
```
> [!NOTE]\
> Multiple snapshots of the same model can be taken. Each call to takeSnapshot() creates a new snapshot instance.

\
You can include extra attributes when taking a snapshot even whey they are **not actual model attributes**.
```php

$model->takeSnapshot([
    'created_by' => auth()->id()
]);
```
> [!TIP]
> These additional attributes can bypass the
> SnapshotDefinition rules - meaning they can be captured even if excluded in the definition:

\
For convenience, the Snapshotable trait adds two relationships for getting the **latest and oldest snapshot** out of the box. 
```php
  
// Get the most recent snapshot
$latest = $model->latestSnapshot;

// Get the first snapshot ever taken
$oldest = $model->oldestSnapshot;
```

\
Snapshots are stored in a separate table with all captured attributes being held in a designated 'storage' 
column in a JSON structure. For more information refer to the **Internal Implementation** section.
***
## Working With Snapshots

Even though snapshot attributes are stored inside a JSON column, you can work with them just like **regular model attributes**.

**Retrieving** \
Access captured attributes **directly** as model properties:
```php
use Dvarilek\CompleteModelSnapshot\Models\Snapshot;

/** @var Snapshot $snapshot */
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

> > [!IMPORTANT]\
> For querying snapshots by these attributes using Eloquent Builder, refer to the **Advanced Usage** section.

***
## Advanced 

### Internal Implementation
#### Storage

Snapshots are stored in a **dedicated table** (model_snapshots) and connected to their original models through a polymorphic relationship. 

The captured attributes are stored in a dedicated **'storage' JSON column** with a structured format that preserves 
all necessary **metadata**.

<br>

* **Base Model Attribute Storage Format**
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

\
Both of these formats have their own **DTO representations**, which are used extensively throughout the package.
* **AttributeTransferObject** 
  * Represents attributes from the base model
* **RelatedAttributeTransferObject**
  * Represents attributes from related models.

<br>

#### Data Manipulation

Working with encoded data directly is possible by hooking into Snapshot model's event handling.

Attributes are decoded from the designated storage column and set as the model's attributes, 
subsequent updates to these attributes are persisted by encoding them into the storage column JSON.

The internal implementation of attribute encoding and decoding is partially derived from [**VirtualColumn Laravel package**](
https://github.com/archtechx/virtualcolumn) (Star it)

### Advanced Usage
#### DTO Usage
Snapshot attributes can be set with **extra metadata** by directly assigning them with a **VirtualAttribute DTO**

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
```
> [!TIP]\
> This allows to **change/set casts** and potentially rename the attribute entirely.
> **This can also be used when adding extra attributes while taking a new snapshots**.

<br>

#### Querying
Since the captured attributes aren't actually the snapshot's real attributes, we 
need to query them in the **JSON column**.
 
For more information about JSON querying see the [**Official Laravel Documentation**](https://laravel.com/docs/10.x/queries#json-where-clauses)

```php
use Dvarilek\CompleteModelSnapshot\Models\Snapshot;

// For captured attribute by the name 'custodian_name'
Snapshot::query()->where('storage->custodian_name->value', $value);
```