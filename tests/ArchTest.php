<?php

declare(strict_types=1);

arch('globals')
    ->expect(['dd', 'dump', 'die', 'var_dump', 'sleep'])
    ->not->toBeUsed();