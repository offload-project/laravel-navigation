<?php

declare(strict_types=1);

namespace OffloadProject\Navigation\Facades;

use Illuminate\Support\Facades\Facade;

final class Navigation extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'navigation';
    }
}
