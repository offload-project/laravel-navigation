<?php

declare(strict_types=1);

use OffloadProject\Navigation\Item;
use OffloadProject\Navigation\ItemBuilder;

if (! function_exists('nav_item')) {
    /**
     * Create a navigation item using the fluent builder.
     *
     * @param  string|Closure  $label  Display label
     * @param  string|null  $route  Route name
     * @param  string|null  $icon  Icon name
     *
     * @example nav_item('Dashboard', 'dashboard', 'home')
     * @example nav_item('Users', 'users.index', 'users')->children([...])
     */
    function nav_item(string|Closure $label = '', ?string $route = null, ?string $icon = null): ItemBuilder
    {
        $item = Item::make($label);

        if ($route !== null) {
            $item->route($route);
        }

        if ($icon !== null) {
            $item->icon($icon);
        }

        return $item;
    }
}

if (! function_exists('nav_separator')) {
    /**
     * Create a navigation separator.
     *
     * @example nav_separator()
     */
    function nav_separator(): ItemBuilder
    {
        return Item::separator();
    }
}

if (! function_exists('nav_divider')) {
    /**
     * Create a navigation divider.
     *
     * @param  string  $spacing  Spacing option
     *
     * @example nav_divider()
     * @example nav_divider('large')
     */
    function nav_divider(string $spacing = 'default'): ItemBuilder
    {
        return Item::divider($spacing);
    }
}

if (! function_exists('nav_external')) {
    /**
     * Create an external link navigation item.
     *
     * @param  string  $label  Display label
     * @param  string  $url  External URL
     * @param  string|null  $icon  Icon name
     *
     * @example nav_external('Documentation', 'https://docs.example.com', 'book')
     */
    function nav_external(string $label, string $url, ?string $icon = null): ItemBuilder
    {
        return Item::external($label, $url, $icon);
    }
}

if (! function_exists('nav_action')) {
    /**
     * Create an action navigation item (POST/DELETE).
     *
     * @param  string  $label  Display label
     * @param  string  $route  Route name
     * @param  string  $method  HTTP method
     * @param  string|null  $icon  Icon name
     *
     * @example nav_action('Logout', 'logout', 'post', 'log-out')
     */
    function nav_action(string $label, string $route, string $method = 'post', ?string $icon = null): ItemBuilder
    {
        return Item::action($label, $route, $method, $icon);
    }
}
