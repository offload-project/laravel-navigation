<?php

declare(strict_types=1);

namespace OffloadProject\Navigation\Data;

use Closure;

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
     * Create a NavigationItem from an array (config format).
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $children = [];
        if (isset($data['children']) && is_array($data['children'])) {
            $children = array_map(
                fn (array $child) => self::fromArray($child),
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
            method: $data['method'] ?? null,
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
}
