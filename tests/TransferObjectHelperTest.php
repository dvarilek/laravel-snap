<?php

use Dvarilek\LaravelSnapshotTree\Helpers\TransferObjectHelper;
use Dvarilek\LaravelSnapshotTree\DTO\RelatedAttributeTransferObject;

test('isRelationTransferObjectFormat returns true for valid relation data', function () {
    $validRelationData = [
        'relationPath' => 'some_relation',
        'value' => 'some_value',
        'attribute' => 'some_attribute',
        'cast' => null,
    ];

    $result = TransferObjectHelper::isRelationTransferObjectFormat($validRelationData);

    expect($result)->toBeTrue();
});

test('isRelationTransferObjectFormat returns false for invalid relation data', function () {
    $invalidRelationData = [
        'value' => 'some_value',
        'attribute' => 'some_attribute',
        'cast' => null,
    ];

    $result = TransferObjectHelper::isRelationTransferObjectFormat($invalidRelationData);

    expect($result)->toBeFalse();
});

test('isAttributeTransferObjectFormat returns true for valid attribute data', function () {
    $validAttributeData = [
        'value' => 'some_value',
        'attribute' => 'some_attribute',
        'cast' => null,
    ];

    $result = TransferObjectHelper::isAttributeTransferObjectFormat($validAttributeData);

    expect($result)->toBeTrue();
});

test('isAttributeTransferObjectFormat returns false for invalid attribute data', function () {
    $invalidAttributeData = [
        'relationPath' => 'some_relation',
        'value' => 'some_value',
        'attribute' => 'some_attribute',
        'cast' => null,
    ];

    $result = TransferObjectHelper::isAttributeTransferObjectFormat($invalidAttributeData);

    expect($result)->toBeFalse();
});

test('createQualifiedRelationName creates name correctly', function () {
    $transferObject = new RelatedAttributeTransferObject(
        attribute: 'some_attribute',
        value: 'some_value',
        cast: 'some cast',
        relationPath: ['firstRelation', 'secondRelation', 'thirdRelation'],
    );

    $result = TransferObjectHelper::createQualifiedRelationName($transferObject);

    expect($result)->toBe("firstRelation_secondRelation_thirdRelation_some_attribute");
});
