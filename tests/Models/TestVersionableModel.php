<?php

namespace Dvarilek\LaravelSnap\Tests\Models;

class TestVersionableModel extends TestRootModel
{

    protected $table = 'test_versionable_models';

    public function getFillable(): array
    {
        return [
            ...$this->fillable,
            'current_version'
        ];
    }

    /**
     * @return ?string
     */
    public static function getCurrentVersionColumn(): ?string
    {
        return "current_version";
    }
}