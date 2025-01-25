<?php

use Dvarilek\LaravelSnapshotTree\DTO\AttributeTransferObject;
use Dvarilek\LaravelSnapshotTree\DTO\RelatedAttributeTransferObject;
use Illuminate\Contracts\Support\Arrayable;
use Dvarilek\LaravelSnapshotTree\Helpers\TransferObjectHelper;

it('correctly initializes and serializes AttributeTransferObject', function () {
    $dto = new AttributeTransferObject(
        attribute: 'test_attribute',
        value: 'test_value',
        cast: 'boolean',
    );

    expect($dto)
        ->toBeInstanceOf(Arrayable::class)
        ->toBeInstanceOf(JsonSerializable::class)
        ->and($dto->attribute)->toBe('test_attribute')
        ->and($dto->value)->toBe('test_value')
        ->and($dto->cast)->toBe('boolean')
        ->and($dto->toArray())->toBe([
            'attribute' => 'test_attribute',
            'value' => 'test_value',
            'cast' => 'boolean',
        ])
        ->and(json_encode($dto))->toBe(json_encode([
            'attribute' => 'test_attribute',
            'value' => 'test_value',
            'cast' => 'boolean',
        ]));
});

it('correctly initializes and serializes RelationTransferObject', function () {
    $dto = new RelatedAttributeTransferObject(
        attribute: 'test_attribute',
        value: 'test_value',
        cast: null,
        relationPath: ['firstRelation', 'secondRelation']
    );

    expect($dto)
        ->toBeInstanceOf(Arrayable::class)
        ->toBeInstanceOf(JsonSerializable::class)
        ->and($dto->attribute)->toBe('test_attribute')
        ->and($dto->value)->toBe('test_value')
        ->and($dto->relationPath)->toBe(['firstRelation', 'secondRelation'])
        ->and($dto->cast)->toBeNull()
        ->and($dto->toArray())->toBe([
            'attribute' => 'test_attribute',
            'value' => 'test_value',
            'cast' => null,
            'relationPath' => ['firstRelation', 'secondRelation'],
        ])
        ->and(json_encode($dto))->toBe(json_encode([
            'attribute' => 'test_attribute',
            'value' => 'test_value',
            'cast' => null,
            'relationPath' => ['firstRelation', 'secondRelation'],
        ]));
});

