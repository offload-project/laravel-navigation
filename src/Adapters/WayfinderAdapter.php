<?php

declare(strict_types=1);

namespace OffloadProject\Navigation\Adapters;

use Illuminate\Support\Facades\Route;
use Laravel\Wayfinder\WayfinderServiceProvider;
use RuntimeException;

final class WayfinderAdapter
{
    /** @var array<string, string> */
    private array $iconMap = [];

    /** @var array<string, string> */
    private array $methodMap = [];

    /** @var array<string, array<string, mixed>> */
    private array $attributeMap = [];

    /** @var array<int, string> */
    private array $excludeRoutes = [];

    private ?string $parentRoute = null;

    /**
     * Create a navigation structure from Wayfinder routes
     */
    public static function fromWayfinder(?string $parentRoute = null): self
    {
        $instance = new self();
        $instance->parentRoute = $parentRoute;

        return $instance;
    }

    /**
     * @param  array<string, string>  $iconMap
     */
    public function withIcons(array $iconMap): self
    {
        $this->iconMap = array_merge($this->iconMap, $iconMap);

        return $this;
    }

    /**
     * @param  array<string, string>  $methodMap
     */
    public function withMethods(array $methodMap): self
    {
        $this->methodMap = array_merge($this->methodMap, $methodMap);

        return $this;
    }

    /**
     * @param  array<string, array<string, mixed>>  $attributeMap
     */
    public function withAttributes(array $attributeMap): self
    {
        $this->attributeMap = array_merge($this->attributeMap, $attributeMap);

        return $this;
    }

    /**
     * @param  array<int, string>  $routeNames
     */
    public function exclude(array $routeNames): self
    {
        $this->excludeRoutes = array_merge($this->excludeRoutes, $routeNames);

        return $this;
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
