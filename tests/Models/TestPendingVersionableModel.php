<?php

namespace Dvarilek\LaravelSnap\Tests\Models;

class TestPendingVersionableModel extends TestRootModel
{

    protected $table = "test_pending_versionable_models";

    public static function getCurrentVersionColumn(): ?string
    {
        return "current_version";
    }

    /**
     * @return list<string>
     */
    public function getFillable(): array
    {
        return array_filter([
            ...parent::getFillable(),
            static::getCurrentVersionColumn(),
        ]);
    }
}