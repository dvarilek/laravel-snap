<?php

use Dvarilek\LaravelSnapshotTree\DTO\RelationTransferObject;
use Illuminate\Contracts\Support\Arrayable;

it('correctly initializes and serializes RelationTransferObject', function () {
    $dto = new RelationTransferObject(
        attribute: 'test_attribute',
        value: 'test_value',
        relationPath: 'test_relation'
    );

    expect($dto)->toBeInstanceOf(Arrayable::class)
        ->toBeInstanceOf(JsonSerializable::class)
        ->and($dto->attribute)->toBe('test_attribute')
        ->and($dto->value)->toBe('test_value')
        ->and($dto->relationPath)->toBe('test_relation')
        ->and($dto->getQualifiedRelationName())->toBe('test_relation_test_attribute')
        ->and($dto->toArray())->toBe([
            'attribute' => 'test_attribute',
            'value' => 'test_value',
            'relationPath' => 'test_relation',
        ])
        ->and(json_encode($dto))->toBe(json_encode([
            'attribute' => 'test_attribute',
            'value' => 'test_value',
            'relationPath' => 'test_relation',
        ]));

});