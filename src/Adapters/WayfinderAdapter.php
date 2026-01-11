<?php

declare(strict_types=1);

namespace OffloadProject\Navigation\Adapters;

use Illuminate\Support\Facades\Route;
use Laravel\Wayfinder\WayfinderServiceProvider;
use RuntimeException;

final class WayfinderAdapter
{
    /**
     * @param  array<string, string>  $iconMap
     * @param  array<string, string>  $methodMap
     * @param  array<string, array<string, mixed>>  $attributeMap
     * @param  array<int, string>  $excludeRoutes
     */
    private function __construct(
        private readonly array $iconMap = [],
        private readonly array $methodMap = [],
        private readonly array $attributeMap = [],
        private readonly array $excludeRoutes = [],
        private readonly ?string $parentRoute = null
    ) {}

    /**
     * Create a navigation structure from Wayfinder routes.
     */
    public static function fromWayfinder(?string $parentRoute = null): self
    {
        return new self(parentRoute: $parentRoute);
    }

    /**
     * Add icon mappings (immutable - returns new instance).
     *
     * @param  array<string, string>  $iconMap
     */
    public function withIcons(array $iconMap): self
    {
        return new self(
            iconMap: array_merge($this->iconMap, $iconMap),
            methodMap: $this->methodMap,
            attributeMap: $this->attributeMap,
            excludeRoutes: $this->excludeRoutes,
            parentRoute: $this->parentRoute,
        );
    }

    /**
     * Add method mappings (immutable - returns new instance).
     *
     * @param  array<string, string>  $methodMap
     */
    public function withMethods(array $methodMap): self
    {
        return new self(
            iconMap: $this->iconMap,
            methodMap: array_merge($this->methodMap, $methodMap),
            attributeMap: $this->attributeMap,
            excludeRoutes: $this->excludeRoutes,
            parentRoute: $this->parentRoute,
        );
    }

    /**
     * Add custom attribute mappings (immutable - returns new instance).
     *
     * @param  array<string, array<string, mixed>>  $attributeMap
     */
    public function withAttributes(array $attributeMap): self
    {
        return new self(
            iconMap: $this->iconMap,
            methodMap: $this->methodMap,
            attributeMap: array_merge($this->attributeMap, $attributeMap),
            excludeRoutes: $this->excludeRoutes,
            parentRoute: $this->parentRoute,
        );
    }

    /**
     * Exclude specific routes (immutable - returns new instance).
     *
     * @param  array<int, string>  $routeNames
     */
    public function exclude(array $routeNames): self
    {
        return new self(
            iconMap: $this->iconMap,
            methodMap: $this->methodMap,
            attributeMap: $this->attributeMap,
            excludeRoutes: array_merge($this->excludeRoutes, $routeNames),
            parentRoute: $this->parentRoute,
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        if (! $this->isWayfinderInstalled()) {
            throw new RuntimeException(
                'Laravel Wayfinder is not installed. Install it with: composer require laravel/wayfinder'
            );
        }

        $routes = $this->getWayfinderRoutes();

        return $this->buildNavigationStructure($routes);
    }

    /**
     * Merge with existing navigation config
     *
     * @param  array<int, array<string, mixed>>  $existingConfig
     * @return array<int, array<string, mixed>>
     */
    public function mergeWith(array $existingConfig): array
    {
        $wayfinderNav = $this->toArray();

        // Merge the arrays, with existing config taking precedence
        return array_merge($wayfinderNav, $existingConfig);
    }

    /**
     * Check if Wayfinder is installed
     */
    private function isWayfinderInstalled(): bool
    {
        return class_exists(WayfinderServiceProvider::class);
    }

    /**
     * Get routes with Wayfinder navigation metadata
     *
     * @return array<int, array<string, mixed>>
     */
    private function getWayfinderRoutes(): array
    {
        $navigationRoutes = [];

        foreach (Route::getRoutes()->getRoutes() as $route) {
            $name = $route->getName();

            if (! $name || in_array($name, $this->excludeRoutes, true)) {
                continue;
            }

            // Check if route has navigation metadata
            $navigation = $route->defaults['navigation'] ?? null;

            if ($navigation) {
                $navigationRoutes[] = [
                    'name' => $name,
                    'label' => $navigation['label'] ?? $this->generateLabelFromRoute($name),
                    'parent' => $navigation['parent'] ?? null,
                    'order' => $navigation['order'] ?? 0,
                    'group' => $navigation['group'] ?? null,
                ];
            }
        }

        // Sort by order
        usort($navigationRoutes, fn ($a, $b) => $a['order'] <=> $b['order']);

        return $navigationRoutes;
    }

    /**
     * Build hierarchical navigation structure
     *
     * @param  array<int, array<string, mixed>>  $routes
     * @return array<int, array<string, mixed>>
     */
    private function buildNavigationStructure(array $routes): array
    {
        $tree = [];
        $grouped = [];

        // Group routes by parent
        foreach ($routes as $route) {
            $parent = $route['parent'] ?? $this->parentRoute;

            if (! isset($grouped[$parent])) {
                $grouped[$parent] = [];
            }

            $grouped[$parent][] = $route;
        }

        // Build tree starting from root (null parent or specified parent)
        $rootKey = $this->parentRoute ?? null;

        foreach ($grouped[$rootKey] ?? [] as $route) {
            $tree[] = $this->buildNavigationItem($route, $grouped);
        }

        return $tree;
    }

    /**
     * Build a single navigation item with children
     *
     * @param  array<string, mixed>  $route
     * @param  array<string, array<int, array<string, mixed>>>  $grouped
     * @return array<string, mixed>
     */
    private function buildNavigationItem(array $route, array $grouped): array
    {
        $routeName = $route['name'];

        $item = [
            'label' => $route['label'],
            'route' => $routeName,
        ];

        // Add icon if mapped
        if (isset($this->iconMap[$routeName])) {
            $item['icon'] = $this->iconMap[$routeName];
        }

        // Add method if mapped
        if (isset($this->methodMap[$routeName])) {
            $item['method'] = $this->methodMap[$routeName];
        }

        // Add custom attributes if mapped
        if (isset($this->attributeMap[$routeName])) {
            $item = array_merge($item, $this->attributeMap[$routeName]);
        }

        // Build children
        if (isset($grouped[$routeName])) {
            $item['children'] = [];
            foreach ($grouped[$routeName] as $child) {
                $item['children'][] = $this->buildNavigationItem($child, $grouped);
            }
        }

        return $item;
    }

    /**
     * Generate a human-readable label from route name
     */
    private function generateLabelFromRoute(string $routeName): string
    {
        $label = preg_replace('/\.(index|show|edit|create|store|update|destroy)$/', '', $routeName);

        // Fix: handle null from preg_replace
        if ($label === null) {
            $label = $routeName;
        }

        $label = str_replace(['.', '-', '_'], ' ', $label);

        return ucwords($label);
    }
}
