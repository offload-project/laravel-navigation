<?php

declare(strict_types=1);

namespace OffloadProject\Navigation\Data;

use Closure;
use OffloadProject\Navigation\Exceptions\InvalidNavigationItemException;
use OffloadProject\Navigation\ItemBuilder;

final readonly class NavigationItem
{
    /**
     * Reserved keys that are handled specially and not passed through as custom attributes.
     */
    public const array RESERVED_KEYS = [
        'label',
        'route',
        'url',
        'method',
        'icon',
        'children',
        'visible',
        'can',
        'breadcrumbOnly',
        'navOnly',
        'params',
    ];

    /**
     * Valid HTTP methods for navigation actions.
     */
    private const array VALID_METHODS = ['get', 'post', 'put', 'patch', 'delete'];

    /**
     * @param  string|Closure  $label  Display label (can be closure for dynamic labels)
     * @param  string|null  $route  Laravel route name
     * @param  string|null  $url  External URL (alternative to route)
     * @param  string|null  $method  HTTP method for actions (e.g., 'post', 'delete')
     * @param  string|null  $icon  Lucide icon name
     * @param  array<int, NavigationItem>  $children  Nested navigation items
     * @param  bool|Closure|null  $visible  Visibility condition
     * @param  string|array<int, mixed>|null  $can  Gate/policy check
     * @param  bool  $breadcrumbOnly  Hide from nav, show in breadcrumbs
     * @param  bool  $navOnly  Show in nav, hide from breadcrumbs
     * @param  array<string, mixed>|null  $params  Route parameters with wildcard support
     * @param  array<string, mixed>  $meta  Custom metadata passed through to frontend
     */
    public function __construct(
        public string|Closure $label,
        public ?string $route = null,
        public ?string $url = null,
        public ?string $method = null,
        public ?string $icon = null,
        public array $children = [],
        public bool|Closure|null $visible = null,
        public string|array|null $can = null,
        public bool $breadcrumbOnly = false,
        public bool $navOnly = false,
        public ?array $params = null,
        public array $meta = [],
    ) {}

    /**
     * Create a NavigationItem from an array or ItemBuilder.
     *
     * Supports multiple formats:
     * - Full array: ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home']
     * - Shorthand indexed: ['Dashboard', 'dashboard', 'home'] (label, route, icon)
     * - Shorthand indexed: ['Dashboard', 'dashboard'] (label, route)
     * - ItemBuilder instance
     *
     * @param  array<string|int, mixed>|ItemBuilder  $data
     *
     * @throws InvalidNavigationItemException If configuration is invalid
     */
    public static function fromArray(array|ItemBuilder $data): self
    {
        // Support ItemBuilder instances
        if ($data instanceof ItemBuilder) {
            $data = $data->toArray();
        }

        // Support shorthand indexed array syntax: ['Label', 'route.name', 'icon']
        $data = self::normalizeShorthand($data);

        self::validate($data);

        $children = [];
        if (isset($data['children']) && is_array($data['children'])) {
            $children = array_map(
                fn (array|ItemBuilder $child) => self::fromArray($child),
                $data['children']
            );
        }

        // Extract custom attributes (non-reserved keys) into meta
        $meta = [];
        foreach ($data as $key => $value) {
            if (! in_array($key, self::RESERVED_KEYS, true)) {
                $meta[$key] = $value;
            }
        }

        return new self(
            label: $data['label'] ?? '',
            route: $data['route'] ?? null,
            url: $data['url'] ?? null,
            method: isset($data['method']) ? mb_strtolower($data['method']) : null,
            icon: $data['icon'] ?? null,
            children: $children,
            visible: $data['visible'] ?? null,
            can: $data['can'] ?? null,
            breadcrumbOnly: $data['breadcrumbOnly'] ?? false,
            navOnly: $data['navOnly'] ?? false,
            params: $data['params'] ?? null,
            meta: $meta,
        );
    }

    /**
     * Check if this item has wildcard parameters.
     */
    public function hasWildcardParams(): bool
    {
        return $this->params !== null && in_array('*', $this->params, true);
    }

    /**
     * Check if this item has a dynamic (closure) label.
     */
    public function hasDynamicLabel(): bool
    {
        return $this->label instanceof Closure;
    }

    /**
     * Check if this item should be skipped in navigation tree.
     * Items with wildcards or dynamic labels should use breadcrumbOnly.
     */
    public function shouldSkipInNavigation(): bool
    {
        if ($this->breadcrumbOnly) {
            return true;
        }

        // Items with wildcards or dynamic labels without breadcrumbOnly flag
        // are problematic and should be skipped with a warning
        return $this->hasWildcardParams() || $this->hasDynamicLabel();
    }

    /**
     * Validate navigation item data.
     *
     * @param  array<string, mixed>  $data
     *
     * @throws InvalidNavigationItemException
     */
    private static function validate(array $data): void
    {
        // Must have a label (string or Closure), children, or be a meta-only item (separator/divider)
        $hasLabel = isset($data['label']) && ($data['label'] instanceof Closure || is_string($data['label']));
        $hasChildren = isset($data['children']) && is_array($data['children']) && count($data['children']) > 0;
        $hasCustomMeta = self::hasCustomMeta($data);

        if (! $hasLabel && ! $hasChildren && ! $hasCustomMeta) {
            throw InvalidNavigationItemException::missingContent($data);
        }

        // Validate method if provided
        if (isset($data['method'])) {
            $method = mb_strtolower($data['method']);

            if (! in_array($method, self::VALID_METHODS, true)) {
                throw InvalidNavigationItemException::invalidMethod($data, $data['method'], self::VALID_METHODS);
            }
        }

        // Cannot have both route and url
        if (isset($data['route']) && isset($data['url'])) {
            throw InvalidNavigationItemException::bothRouteAndUrl($data);
        }

        // Cannot be both breadcrumbOnly and navOnly
        if (! empty($data['breadcrumbOnly']) && ! empty($data['navOnly'])) {
            throw InvalidNavigationItemException::conflictingVisibility($data);
        }

        // Params only make sense with a route
        if (isset($data['params']) && ! isset($data['route'])) {
            throw InvalidNavigationItemException::paramsWithoutRoute($data);
        }
    }

    /**
     * Normalize shorthand array syntax to full associative array.
     *
     * Converts: ['Dashboard', 'dashboard', 'home']
     * To: ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home']
     *
     * @param  array<string|int, mixed>  $data
     * @return array<string, mixed>
     */
    private static function normalizeShorthand(array $data): array
    {
        // Check if this is a shorthand indexed array (numeric keys starting at 0)
        if (! self::isShorthandArray($data)) {
            return $data;
        }

        $normalized = [];

        // Position 0: label (required for shorthand)
        if (isset($data[0])) {
            $normalized['label'] = $data[0];
        }

        // Position 1: route (optional)
        if (isset($data[1]) && is_string($data[1])) {
            // Check if it looks like a URL
            if (str_starts_with($data[1], 'http://') || str_starts_with($data[1], 'https://')) {
                $normalized['url'] = $data[1];
            } else {
                $normalized['route'] = $data[1];
            }
        }

        // Position 2: icon (optional)
        if (isset($data[2]) && is_string($data[2])) {
            $normalized['icon'] = $data[2];
        }

        // Position 3: children (optional)
        if (isset($data[3]) && is_array($data[3])) {
            $normalized['children'] = $data[3];
        }

        return $normalized;
    }

    /**
     * Check if an array is shorthand format (sequential numeric keys).
     *
     * @param  array<string|int, mixed>  $data
     */
    private static function isShorthandArray(array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        // Check if it has numeric keys starting at 0
        $keys = array_keys($data);

        // Must have at least one numeric key at position 0
        if (! isset($data[0])) {
            return false;
        }

        // First value must be a string (label) or Closure
        if (! is_string($data[0]) && ! ($data[0] instanceof Closure)) {
            return false;
        }

        // If it has string keys like 'label', 'route', etc., it's not shorthand
        foreach ($keys as $key) {
            if (is_string($key) && in_array($key, self::RESERVED_KEYS, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if data has custom metadata (non-reserved keys).
     *
     * @param  array<string|int, mixed>  $data
     */
    private static function hasCustomMeta(array $data): bool
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && ! in_array($key, self::RESERVED_KEYS, true)) {
                return true;
            }
        }

        return false;
    }
}
