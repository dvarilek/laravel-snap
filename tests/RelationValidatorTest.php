<?php

use Dvarilek\CompleteModelSnapshot\Exceptions\InvalidRelationException;
use Dvarilek\CompleteModelSnapshot\Tests\Models\TestRootModel;
use Dvarilek\CompleteModelSnapshot\Support\RelationValidator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;

test('RelationValidator throws exception for non-existent relation', function () {
     expect(fn () => RelationValidator::assertValid(new TestRootModel(), 'nonexistentrelation'))
         ->toThrow(InvalidRelationException::class);
});

test('RelationValidator throws exception for invalid relation type', function (string $relationMethod, array $relationArgs) {
    $childModel = new class extends Model {
        protected $table = 'test_children';
    };

    $model = new class extends TestRootModel {
        public string $relationMethod;

        public array $relationArgs;

        public function invalidRelation(): Relation
        {
            return $this->{$this->relationMethod}(...$this->relationArgs);
        }
    };

    $model->relationMethod = $relationMethod;
    array_unshift($relationArgs, $childModel);
    $model->relationArgs = $relationArgs;

    expect(fn () => RelationValidator::assertValid($model, 'invalidRelation'))
        ->toThrow(InvalidRelationException::class);
})->with([
    ['hasMany', []],
    ['belongsToMany', []],
    ['hasOne', []],
    ['morphMany', ['morphable']],
    ['morphOne', ['morphable']],
    ['morphToMany', ['taggable']],
    ['morphedByMany', ['taggable']],
]);

test('RelationValidator does not throw exception for valid relation', function () {
    expect(fn () => RelationValidator::assertValid(new TestRootModel(), 'parent'))
        ->not->toThrow(InvalidRelationException::class);
});