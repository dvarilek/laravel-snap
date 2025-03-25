<?php

declare(strict_types=1);

namespace Dvarilek\LaravelSnap\DTO\Contracts;

use Illuminate\Contracts\Support\Arrayable;

/** @phpstan-ignore missingType.generics */
interface VirtualAttribute extends \JsonSerializable, Arrayable
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}