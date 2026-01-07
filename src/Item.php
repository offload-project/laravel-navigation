<?php

declare(strict_types=1);

namespace OffloadProject\Navigation;

use Closure;

/**
 * Fluent builder for navigation items.
 *
 * Alias for ItemBuilder with a shorter, more intuitive name.
 *
 * @example
 * use OffloadProject\Navigation\Item;
 *
 * Item::make('Dashboard')
 *     ->route('dashboard')
 *     ->icon('home')
 *     ->visible(fn () => auth()->check());
 *
 * @see ItemBuilder
 */
final class Item extends ItemBuilder
{
    /**
     * Create a new navigation item.
     *
     * @param  string|Closure  $label  Display label
     * @param  string|null  $route  Optional route name
     * @param  string|null  $icon  Optional icon name
     */
    public static function to(string|Closure $label, ?string $route = null, ?string $icon = null): self
    {
        $item = new self($label);

        if ($route !== null) {
            $item->route($route);
        }

        if ($icon !== null) {
            $item->icon($icon);
        }

        return $item;
    }

    /**
     * Create a link to an external URL.
     *
     * @param  string  $label  Display label
     * @param  string  $url  External URL
     * @param  string|null  $icon  Optional icon name
     */
    public static function external(string $label, string $url, ?string $icon = null): self
    {
        $item = new self($label);
        $item->url($url);

        if ($icon !== null) {
            $item->icon($icon);
        }

        return $item;
    }

    /**
     * Create an action item (POST/DELETE request).
     *
     * @param  string  $label  Display label
     * @param  string  $route  Route name
     * @param  string  $method  HTTP method (post, delete, etc.)
     * @param  string|null  $icon  Optional icon name
     */
    public static function action(string $label, string $route, string $method = 'post', ?string $icon = null): self
    {
        $item = new self($label);
        $item->route($route);
        $item->method($method);

        if ($icon !== null) {
            $item->icon($icon);
        }

        return $item;
    }
}
