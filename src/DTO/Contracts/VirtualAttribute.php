<?php

declare(strict_types=1);

namespace Dvarilek\LaravelSnapshotTree\DTO\Contracts;

use Illuminate\Contracts\Support\Arrayable;

interface VirtualAttribute extends \JsonSerializable, Arrayable {}