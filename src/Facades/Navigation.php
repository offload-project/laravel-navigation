<?php

declare(strict_types=1);

namespace OffloadProject\Navigation\Facades;

use Illuminate\Support\Facades\Facade;
use OffloadProject\Navigation\Navigation as NavigationInstance;
use OffloadProject\Navigation\NavigationBuilder;
use OffloadProject\Navigation\NavigationManager;

/**
 * @method static NavigationInstance get(string $name)
 * @method static NavigationBuilder register(string $name)
 * @method static NavigationManager addNavigation(string $name, array $items)
 * @method static bool has(string $name)
 * @method static array breadcrumbs(?string $name = null, ?string $routeName = null)
 * @method static array getAllNavigations()
 * @method static array names()
 * @method static void clearCache()
 * @method static void clearAll()
 *
 * @see NavigationManager
 */
final class Navigation extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'navigation';
    }
}
