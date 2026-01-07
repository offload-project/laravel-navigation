<?php

declare(strict_types=1);

namespace OffloadProject\Navigation\Contracts;

interface NavigationInterface
{
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
    ): array;

    /**
     * Get the breadcrumb trail for a route.
     *
     * @param  string|null  $routeName  Route name (defaults to current route)
     * @param  array<string, mixed>  $routeParams  Route parameters
     * @return array<int, array<string, mixed>>
     */
    public function breadcrumbs(?string $routeName = null, array $routeParams = []): array;

    /**
     * Build the navigation tree for rendering.
     *
     * @deprecated Use items() instead. Will be removed in v2.0.
     *
     * @param  array<string, mixed>  $routeParams  Route parameters for URL generation
     * @param  string|null  $currentRoute  Current route name (defaults to request route)
     * @param  array<string, mixed>  $currentRouteParams  Current route parameters (defaults to request params)
     * @return array<int, array<string, mixed>>
     */
    public function toTree(
        array $routeParams = [],
        ?string $currentRoute = null,
        array $currentRouteParams = []
    ): array;

    /**
     * Get breadcrumbs for a given route.
     *
     * @deprecated Use breadcrumbs() instead. Will be removed in v2.0.
     *
     * @param  array<string, mixed>  $routeParams
     * @return array<int, array<string, mixed>>
     */
    public function getBreadcrumbs(string $currentRouteName, array $routeParams = []): array;
}
