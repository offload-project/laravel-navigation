<?php

declare(strict_types=1);

namespace OffloadProject\Navigation;

use Closure;

/**
 * Fluent builder for registering navigation at runtime.
 *
 * Note: This builder only supports one level of nesting via child().
 * For deeper nesting, use Item::make() with children() or the add() method.
 *
 * @example
 * Navigation::register('main')
 *     ->item('Dashboard', 'dashboard', 'home')
 *     ->item('Users', 'users.index', 'users')
 *         ->child('All Users', 'users.index')
 *         ->child('Roles', 'roles.index')
 *     ->item('Settings', 'settings.index', 'settings');
 */
final class NavigationBuilder
{
    /** @var array<int, array<string, mixed>> */
    private array $items = [];

    /** @var array<int, int> Stack of parent indices for nesting */
    private array $parentStack = [];

    public function __construct(
        private readonly string $name,
        private readonly NavigationManager $manager
    ) {}

    /**
     * Add a navigation item.
     *
     * @param  string|Closure  $label  Display label
     * @param  string|null  $route  Route name
     * @param  string|null  $icon  Icon name
     */
    public function item(string|Closure $label, ?string $route = null, ?string $icon = null): self
    {
        // Reset to root level when adding a new item
        $this->parentStack = [];

        $item = ['label' => $label];

        if ($route !== null) {
            $item['route'] = $route;
        }

        if ($icon !== null) {
            $item['icon'] = $icon;
        }

        $this->items[] = $item;

        // Track this item's index for potential children
        $this->parentStack[] = count($this->items) - 1;

        return $this;
    }

    /**
     * Add an external link item.
     *
     * @param  string  $label  Display label
     * @param  string  $url  External URL
     * @param  string|null  $icon  Icon name
     */
    public function external(string $label, string $url, ?string $icon = null): self
    {
        $this->parentStack = [];

        $item = ['label' => $label, 'url' => $url];

        if ($icon !== null) {
            $item['icon'] = $icon;
        }

        $this->items[] = $item;
        $this->parentStack[] = count($this->items) - 1;

        return $this;
    }

    /**
     * Add an action item (POST/DELETE).
     *
     * @param  string  $label  Display label
     * @param  string  $route  Route name
     * @param  string  $method  HTTP method
     * @param  string|null  $icon  Icon name
     */
    public function action(string $label, string $route, string $method = 'post', ?string $icon = null): self
    {
        $this->parentStack = [];

        $item = ['label' => $label, 'route' => $route, 'method' => $method];

        if ($icon !== null) {
            $item['icon'] = $icon;
        }

        $this->items[] = $item;
        $this->parentStack[] = count($this->items) - 1;

        return $this;
    }

    /**
     * Add a child to the last added item.
     *
     * @param  string|Closure  $label  Display label
     * @param  string|null  $route  Route name
     * @param  string|null  $icon  Icon name
     */
    public function child(string|Closure $label, ?string $route = null, ?string $icon = null): self
    {
        if (empty($this->parentStack)) {
            // No parent, add as root item
            return $this->item($label, $route, $icon);
        }

        $parentIndex = end($this->parentStack);

        $child = ['label' => $label];

        if ($route !== null) {
            $child['route'] = $route;
        }

        if ($icon !== null) {
            $child['icon'] = $icon;
        }

        if (! isset($this->items[$parentIndex]['children'])) {
            $this->items[$parentIndex]['children'] = [];
        }

        $this->items[$parentIndex]['children'][] = $child;

        return $this;
    }

    /**
     * Add a separator.
     */
    public function separator(): self
    {
        $this->parentStack = [];
        $this->items[] = ['separator' => true];

        return $this;
    }

    /**
     * Add a divider.
     */
    public function divider(string $spacing = 'default'): self
    {
        $this->parentStack = [];
        $this->items[] = ['divider' => $spacing];

        return $this;
    }

    /**
     * Add a raw item configuration.
     *
     * @param  array<string, mixed>|ItemBuilder  $item
     */
    public function add(array|ItemBuilder $item): self
    {
        $this->parentStack = [];

        if ($item instanceof ItemBuilder) {
            $item = $item->toArray();
        }

        $this->items[] = $item;
        $this->parentStack[] = count($this->items) - 1;

        return $this;
    }

    /**
     * Get the navigation configuration as array.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Register the navigation and return the manager for chaining.
     */
    public function done(): NavigationManager
    {
        $this->manager->addNavigation($this->name, $this->items);

        return $this->manager;
    }

    /**
     * Get the built navigation instance.
     */
    public function get(): Navigation
    {
        $this->done();

        return $this->manager->get($this->name);
    }
}
