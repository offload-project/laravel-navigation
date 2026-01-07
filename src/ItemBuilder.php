<?php

declare(strict_types=1);

namespace OffloadProject\Navigation;

use Closure;

/**
 * Fluent builder for navigation items with full IDE autocomplete support.
 *
 * @example
 * Item::make('Dashboard')
 *     ->route('dashboard')
 *     ->icon('home')
 *     ->visible(fn () => auth()->check())
 *     ->can('view-dashboard');
 *
 * @phpstan-consistent-constructor
 */
class ItemBuilder
{
    protected string|Closure $label;

    protected ?string $route = null;

    protected ?string $url = null;

    protected ?string $method = null;

    protected ?string $icon = null;

    /** @var array<int, array<string, mixed>|ItemBuilder> */
    protected array $children = [];

    protected bool|Closure|null $visible = null;

    /** @var string|array<int, mixed>|null */
    protected string|array|null $can = null;

    protected bool $breadcrumbOnly = false;

    protected bool $navOnly = false;

    /** @var array<string, mixed>|null */
    protected ?array $params = null;

    /** @var array<string, mixed> */
    protected array $meta = [];

    /**
     * Create a new navigation item builder.
     */
    public function __construct(string|Closure $label = '')
    {
        $this->label = $label;
    }

    /**
     * Create a new navigation item builder.
     *
     * @example Item::make('Dashboard')->route('dashboard')
     */
    public static function make(string|Closure $label = ''): static
    {
        return new static($label);
    }

    /**
     * Mark this item as a separator/divider.
     *
     * @example Item::separator()
     */
    public static function separator(): static
    {
        $item = new static();
        $item->meta['separator'] = true;

        return $item;
    }

    /**
     * Mark this item as a divider with optional spacing.
     *
     * @example Item::divider()
     * @example Item::divider('large')
     */
    public static function divider(string $spacing = 'default'): static
    {
        $item = new static();
        $item->meta['divider'] = $spacing;

        return $item;
    }

    /**
     * Set the display label.
     *
     * @param  string|Closure  $label  Display text or closure for dynamic labels
     *
     * @example ->label('Dashboard')
     * @example ->label(fn ($user) => "Edit {$user->name}")
     */
    public function label(string|Closure $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Set the Laravel route name.
     *
     * @param  string  $route  Laravel route name (e.g., 'users.index')
     *
     * @example ->route('users.index')
     * @example ->route('users.show')
     */
    public function route(string $route): static
    {
        $this->route = $route;

        return $this;
    }

    /**
     * Set an external URL (alternative to route).
     *
     * @param  string  $url  External URL
     *
     * @example ->url('https://docs.example.com')
     */
    public function url(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Set the HTTP method for action items.
     *
     * @param  string  $method  HTTP method (get, post, put, patch, delete)
     *
     * @example ->method('post') // For logout buttons
     * @example ->method('delete') // For delete actions
     */
    public function method(string $method): static
    {
        $this->method = $method;

        return $this;
    }

    /**
     * Set the Lucide icon name.
     *
     * @param  string  $icon  Lucide icon name (e.g., 'home', 'users', 'settings')
     *
     * @see https://lucide.dev/icons
     *
     * @example ->icon('home')
     * @example ->icon('users')
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Add child navigation items.
     *
     * @param  array<int, array<string, mixed>|ItemBuilder>  $children  Child items
     *
     * @example ->children([
     *     Item::make('All Users')->route('users.index'),
     *     Item::make('Roles')->route('roles.index'),
     * ])
     */
    public function children(array $children): static
    {
        $this->children = $children;

        return $this;
    }

    /**
     * Add a single child navigation item.
     *
     * @example ->child(Item::make('Settings')->route('settings'))
     */
    public function child(self|array $child): static
    {
        $this->children[] = $child;

        return $this;
    }

    /**
     * Set visibility condition.
     *
     * @param  bool|Closure  $visible  Boolean or closure returning boolean
     *
     * @example ->visible(false)
     * @example ->visible(fn () => auth()->check())
     * @example ->visible(fn () => config('features.beta'))
     */
    public function visible(bool|Closure $visible): static
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Set visibility to only show when user is authenticated.
     *
     * @example ->whenAuthenticated()
     */
    public function whenAuthenticated(): static
    {
        $this->visible = fn () => auth()->check();

        return $this;
    }

    /**
     * Set visibility to only show when user is a guest.
     *
     * @example ->whenGuest()
     */
    public function whenGuest(): static
    {
        $this->visible = fn () => auth()->guest();

        return $this;
    }

    /**
     * Set gate/policy authorization check.
     *
     * @param  string|array<int, mixed>  $ability  Gate name or [ability, model] for policies
     *
     * @example ->can('view-dashboard')
     * @example ->can(['update', $post])
     */
    public function can(string|array $ability): static
    {
        $this->can = $ability;

        return $this;
    }

    /**
     * Mark item to only appear in breadcrumbs (hidden from navigation tree).
     * Use for edit/show pages that shouldn't appear in main nav.
     *
     * @example ->breadcrumbOnly()
     */
    public function breadcrumbOnly(bool $value = true): static
    {
        $this->breadcrumbOnly = $value;

        return $this;
    }

    /**
     * Mark item to only appear in navigation tree (hidden from breadcrumbs).
     *
     * @example ->navOnly()
     */
    public function navOnly(bool $value = true): static
    {
        $this->navOnly = $value;

        return $this;
    }

    /**
     * Set route parameters with optional wildcard support.
     *
     * @param  array<string, mixed>  $params  Route parameters (use '*' for wildcards)
     *
     * @example ->params(['user' => 5])
     * @example ->params(['user' => '*']) // Matches any user ID
     */
    public function params(array $params): static
    {
        $this->params = $params;

        return $this;
    }

    /**
     * Add custom metadata for frontend use.
     *
     * @param  string  $key  Metadata key
     * @param  mixed  $value  Metadata value
     *
     * @example ->meta('badge', 5)
     * @example ->meta('badgeColor', 'red')
     */
    public function meta(string $key, mixed $value): static
    {
        $this->meta[$key] = $value;

        return $this;
    }

    /**
     * Add a badge to the item.
     *
     * @example ->badge(5)
     * @example ->badge(fn () => Notification::unreadCount())
     */
    public function badge(int|string|Closure $count, string $color = 'default'): static
    {
        $this->meta['badge'] = $count;
        $this->meta['badgeColor'] = $color;

        return $this;
    }

    /**
     * Convert the builder to an array for configuration.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $item = [];

        if ($this->label !== '') {
            $item['label'] = $this->label;
        }

        if ($this->route !== null) {
            $item['route'] = $this->route;
        }

        if ($this->url !== null) {
            $item['url'] = $this->url;
        }

        if ($this->method !== null) {
            $item['method'] = $this->method;
        }

        if ($this->icon !== null) {
            $item['icon'] = $this->icon;
        }

        if ($this->children !== []) {
            $item['children'] = array_map(
                fn ($child) => $child instanceof self ? $child->toArray() : $child,
                $this->children
            );
        }

        if ($this->visible !== null) {
            $item['visible'] = $this->visible;
        }

        if ($this->can !== null) {
            $item['can'] = $this->can;
        }

        if ($this->breadcrumbOnly) {
            $item['breadcrumbOnly'] = true;
        }

        if ($this->navOnly) {
            $item['navOnly'] = true;
        }

        if ($this->params !== null) {
            $item['params'] = $this->params;
        }

        // Merge meta directly into item (not nested)
        foreach ($this->meta as $key => $value) {
            $item[$key] = $value;
        }

        return $item;
    }
}
