<?php

declare(strict_types=1);

namespace OffloadProject\Navigation;

use Closure;
use Illuminate\Routing\Exceptions\UrlGenerationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use InvalidArgumentException;
use OffloadProject\Navigation\Contracts\IconCompilerInterface;
use OffloadProject\Navigation\Contracts\NavigationInterface;
use OffloadProject\Navigation\Data\NavigationItem;
use OffloadProject\Navigation\Support\ItemVisibilityResolver;

final class Navigation implements NavigationInterface
{
    private string $name;

    /** @var array<int, NavigationItem> */
    private array $items;

    private IconCompilerInterface $iconCompiler;

    private ItemVisibilityResolver $visibilityResolver;

    /**
     * @param  array<int, array<string, mixed>>  $items  Raw config items
     */
    public function __construct(
        string $name,
        array $items,
        IconCompilerInterface $iconCompiler,
        ItemVisibilityResolver $visibilityResolver
    ) {
        $this->name = $name;
        $this->items = array_map(
            fn (array $item) => NavigationItem::fromArray($item),
            $items
        );
        $this->iconCompiler = $iconCompiler;
        $this->visibilityResolver = $visibilityResolver;
    }

    /**
     * Get the navigation items as an array tree.
     *
     * @param  array<string, mixed>  $routeParams  Route parameters for URL generation
     * @param  string|null  $currentRoute  Current route name (defaults to request route)
     * @param  array<string, mixed>  $currentRouteParams  Current route parameters (defaults to request params)
     * @return array<int, array<string, mixed>>
     */
    public function items(
        array $routeParams = [],
        ?string $currentRoute = null,
        array $currentRouteParams = []
    ): array {
        $currentRoute ??= request()->route()?->getName();
        $currentRouteParams = $currentRouteParams ?: (request()->route()?->parameters() ?? []);

        return $this->buildTree($this->items, $routeParams, $currentRoute, $currentRouteParams);
    }

    /**
     * Get the navigation items as an array tree.
     *
     * @deprecated Use items() instead. Will be removed in v2.0.
     *
     * @param  array<string, mixed>  $routeParams
     * @param  string|null  $currentRoute  Current route name (defaults to request route)
     * @param  array<string, mixed>  $currentRouteParams  Current route parameters (defaults to request params)
     * @return array<int, array<string, mixed>>
     */
    public function toTree(
        array $routeParams = [],
        ?string $currentRoute = null,
        array $currentRouteParams = []
    ): array {
        trigger_deprecation(
            'offload-project/laravel-navigation',
            '1.1',
            'Method "%s::toTree()" is deprecated, use "items()" instead.',
            self::class
        );

        return $this->items($routeParams, $currentRoute, $currentRouteParams);
    }

    /**
     * Get the breadcrumb trail for a route.
     *
     * @param  string|null  $routeName  Route name (defaults to current route)
     * @param  array<string, mixed>  $routeParams  Route parameters
     * @return array<int, array<string, mixed>>
     */
    public function breadcrumbs(?string $routeName = null, array $routeParams = []): array
    {
        $routeName ??= request()->route()?->getName();

        if ($routeName === null) {
            return [];
        }

        $routeParams = $routeParams ?: (request()->route()?->parameters() ?? []);

        return $this->findBreadcrumbPath($this->items, $routeName, [], $routeParams);
    }

    /**
     * Get the breadcrumb trail for a route.
     *
     * @deprecated Use breadcrumbs() instead. Will be removed in v2.0.
     *
     * @param  array<string, mixed>  $routeParams
     * @return array<int, array<string, mixed>>
     */
    public function getBreadcrumbs(string $currentRouteName, array $routeParams = []): array
    {
        trigger_deprecation(
            'offload-project/laravel-navigation',
            '1.1',
            'Method "%s::getBreadcrumbs()" is deprecated, use "breadcrumbs()" instead.',
            self::class
        );

        return $this->breadcrumbs($currentRouteName, $routeParams);
    }

    /**
     * @param  array<int, NavigationItem>  $items
     * @param  array<string, mixed>  $routeParams
     * @param  array<string, mixed>  $currentRouteParams
     * @return array<int, array<string, mixed>>
     */
    private function buildTree(
        array $items,
        array $routeParams,
        ?string $currentRoute,
        array $currentRouteParams,
        ?string $parentId = null
    ): array {
        $tree = [];

        foreach ($items as $index => $item) {
            if (! $this->shouldIncludeInTree($item)) {
                continue;
            }

            $id = $this->generateNodeId($index, $parentId);
            $node = $this->buildNode($item, $id, $routeParams, $currentRoute, $currentRouteParams);
            $node['children'] = $this->buildTree(
                $item->children,
                $routeParams,
                $currentRoute,
                $currentRouteParams,
                $id
            );

            $tree[] = $node;
        }

        return $tree;
    }

    /**
     * Check if item should be included in the navigation tree.
     */
    private function shouldIncludeInTree(NavigationItem $item): bool
    {
        // Check visibility and permissions
        if (! $this->visibilityResolver->isVisible($item)) {
            return false;
        }

        // Skip breadcrumb-only items
        if ($item->breadcrumbOnly) {
            return false;
        }

        // Skip items with wildcards or dynamic labels (should use breadcrumbOnly)
        if ($item->hasWildcardParams() || $item->hasDynamicLabel()) {
            $this->logSkippedItem($item);

            return false;
        }

        return true;
    }

    /**
     * Log a warning for items that should use breadcrumbOnly.
     */
    private function logSkippedItem(NavigationItem $item): void
    {
        if (! config('app.debug')) {
            return;
        }

        Log::warning(
            "Navigation item skipped: Items with wildcard params or dynamic labels should use 'breadcrumbOnly' => true",
            [
                'navigation' => $this->name,
                'label' => $item->hasDynamicLabel() ? '[closure]' : $item->label,
                'route' => $item->route,
            ]
        );
    }

    /**
     * Generate a unique node ID.
     */
    private function generateNodeId(int $index, ?string $parentId): string
    {
        return $parentId
            ? "{$parentId}-{$index}"
            : "nav-{$this->name}-{$index}";
    }

    /**
     * Build a single navigation node from an item.
     *
     * @param  array<string, mixed>  $routeParams
     * @param  array<string, mixed>  $currentRouteParams
     * @return array<string, mixed>
     */
    private function buildNode(
        NavigationItem $item,
        string $id,
        array $routeParams,
        ?string $currentRoute,
        array $currentRouteParams
    ): array {
        $node = ['id' => $id];

        // Add label and active state
        if ($item->label !== '') {
            $node['label'] = $item->label;
            $node['isActive'] = $this->isActive($item, $currentRoute, $currentRouteParams);
            $node['children'] = [];
        }

        // Add URL
        $node['url'] = $this->resolveUrl($item, $routeParams);

        // Add method if present
        if ($item->method !== null) {
            $node['method'] = $item->method;
        }

        // Add icon if present
        if ($item->icon !== null) {
            $node['icon'] = $this->iconCompiler->compile($item->icon);
        }

        // Add custom metadata
        foreach ($item->meta as $key => $value) {
            $node[$key] = $value;
        }

        return $node;
    }

    /**
     * Resolve the URL for a navigation item.
     *
     * @param  array<string, mixed>  $routeParams
     */
    private function resolveUrl(NavigationItem $item, array $routeParams): ?string
    {
        if ($item->route !== null) {
            return $this->resolveRoute($item->route, $routeParams);
        }

        return $item->url;
    }

    /**
     * Check if a navigation item is active.
     *
     * @param  array<string, mixed>  $currentRouteParams
     */
    private function isActive(NavigationItem $item, ?string $currentRoute, array $currentRouteParams): bool
    {
        if ($currentRoute === null) {
            return false;
        }

        if ($item->route !== null) {
            // Check if current route matches with wildcard params
            if ($this->routeMatches($item->route, $currentRoute, $item->params, $currentRouteParams)) {
                return true;
            }

            // Check if current route is a child route (e.g., users.index matches users.*)
            if (Str::startsWith($currentRoute, $item->route.'.')) {
                return true;
            }
        }

        // Check children (including breadcrumbOnly items for active state)
        foreach ($item->children as $child) {
            if ($this->isActive($child, $currentRoute, $currentRouteParams)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if a route matches with wildcard parameter support.
     *
     * @param  array<string, mixed>|null  $itemParams
     * @param  array<string, mixed>  $currentParams
     */
    private function routeMatches(
        string $itemRoute,
        string $currentRoute,
        ?array $itemParams,
        array $currentParams
    ): bool {
        if ($itemRoute !== $currentRoute) {
            return false;
        }

        // If no params specified, exact route match is enough
        if ($itemParams === null) {
            return true;
        }

        // Check if all wildcard params are present in current params
        foreach ($itemParams as $key => $value) {
            if ($value === '*') {
                // Wildcard - just check the param exists
                if (! isset($currentParams[$key])) {
                    return false;
                }
            } else {
                // Exact match required
                if (! isset($currentParams[$key]) || $currentParams[$key] !== $value) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Resolve a route name to a URL.
     *
     * @param  array<string, mixed>  $params
     */
    private function resolveRoute(string $routeName, array $params = []): string
    {
        try {
            return route($routeName, $params);
        } catch (UrlGenerationException $e) {
            $this->logRouteError($routeName, $e->getMessage());

            return '#';
        } catch (InvalidArgumentException $e) {
            // Route doesn't exist
            $this->logRouteError($routeName, $e->getMessage());

            return '#';
        }
    }

    /**
     * Log a route resolution error.
     */
    private function logRouteError(string $routeName, string $message): void
    {
        if (! config('app.debug')) {
            return;
        }

        Log::warning('Navigation route error', [
            'navigation' => $this->name,
            'route' => $routeName,
            'error' => $message,
        ]);
    }

    /**
     * Find the breadcrumb path to a target route.
     *
     * @param  array<int, NavigationItem>  $items
     * @param  array<int, array<string, mixed>>  $currentPath
     * @param  array<string, mixed>  $routeParams
     * @return array<int, array<string, mixed>>
     */
    private function findBreadcrumbPath(
        array $items,
        string $targetRoute,
        array $currentPath,
        array $routeParams
    ): array {
        foreach ($items as $index => $item) {
            // Skip nav-only items but still check their children
            if ($item->navOnly) {
                $result = $this->findBreadcrumbPath($item->children, $targetRoute, $currentPath, $routeParams);
                if (! empty($result)) {
                    return $result;
                }

                continue;
            }

            $breadcrumbItem = $this->buildBreadcrumbItem($item, $currentPath, $index, $routeParams);
            $newPath = [...$currentPath, $breadcrumbItem];

            // Check if this is the target (with wildcard support)
            if ($item->route !== null && $this->routeMatches($item->route, $targetRoute, $item->params, $routeParams)) {
                return $this->resolveBreadcrumbLabels($newPath, $routeParams);
            }

            // Check children
            $result = $this->findBreadcrumbPath($item->children, $targetRoute, $newPath, $routeParams);
            if (! empty($result)) {
                return $result;
            }
        }

        return [];
    }

    /**
     * Build a breadcrumb item from a navigation item.
     *
     * @param  array<int, array<string, mixed>>  $currentPath
     * @param  array<string, mixed>  $routeParams
     * @return array<string, mixed>
     */
    private function buildBreadcrumbItem(
        NavigationItem $item,
        array $currentPath,
        int $index,
        array $routeParams
    ): array {
        $breadcrumbItem = [
            'id' => $this->generateBreadcrumbId($currentPath, $index),
            'label' => $item->label, // Keep original (string or closure) until final resolution
        ];

        if ($item->route !== null) {
            $breadcrumbItem['route'] = $item->route;

            // For breadcrumb-only items with wildcards, use current route params
            if ($item->breadcrumbOnly && $item->params !== null) {
                $resolvedParams = $this->resolveWildcardParams($item->params, $routeParams);
                $breadcrumbItem['url'] = $this->resolveRoute($item->route, $resolvedParams);
            } else {
                $breadcrumbItem['url'] = $this->resolveRoute($item->route, $routeParams);
            }
        } elseif ($item->url !== null) {
            $breadcrumbItem['url'] = $item->url;
        }

        return $breadcrumbItem;
    }

    /**
     * Resolve all closure labels in a breadcrumb path.
     *
     * @param  array<int, array<string, mixed>>  $path
     * @param  array<string, mixed>  $routeParams
     * @return array<int, array<string, mixed>>
     */
    private function resolveBreadcrumbLabels(array $path, array $routeParams): array
    {
        return array_map(function (array $item) use ($routeParams): array {
            if ($item['label'] instanceof Closure) {
                $item['label'] = $this->resolveLabel($item['label'], $routeParams);
            }

            return $item;
        }, $path);
    }

    /**
     * Resolve a label that might be a string or closure.
     *
     * @param  array<string, mixed>  $routeParams
     */
    private function resolveLabel(string|Closure $label, array $routeParams): string
    {
        if (! ($label instanceof Closure)) {
            return $label;
        }

        // Extract model instances from route params for convenience
        $models = array_filter($routeParams, fn ($param) => is_object($param));

        // If there's only one model, pass it directly, otherwise pass all params
        if (count($models) === 1) {
            return $label(reset($models));
        }

        return $label($routeParams);
    }

    /**
     * Resolve wildcard parameters with actual values.
     *
     * @param  array<string, mixed>  $itemParams
     * @param  array<string, mixed>  $currentParams
     * @return array<string, mixed>
     */
    private function resolveWildcardParams(array $itemParams, array $currentParams): array
    {
        $resolved = [];

        foreach ($itemParams as $key => $value) {
            if ($value === '*' && isset($currentParams[$key])) {
                $param = $currentParams[$key];

                // Convert model instances to their route keys
                if (is_object($param)) {
                    if (method_exists($param, 'getRouteKey')) {
                        $resolved[$key] = $param->getRouteKey();
                    } elseif (method_exists($param, '__toString')) {
                        $resolved[$key] = (string) $param;
                    } else {
                        $resolved[$key] = $param;
                    }
                } else {
                    $resolved[$key] = $param;
                }
            } elseif ($value !== '*') {
                $resolved[$key] = $value;
            }
        }

        return $resolved;
    }

    /**
     * Generate a unique breadcrumb ID.
     *
     * @param  array<int, array<string, mixed>>  $currentPath
     */
    private function generateBreadcrumbId(array $currentPath, int $index): string
    {
        $depth = count($currentPath);

        return "breadcrumb-{$this->name}-{$depth}-{$index}";
    }
}
