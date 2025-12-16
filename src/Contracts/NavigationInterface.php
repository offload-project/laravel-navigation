<?php

declare(strict_types=1);

namespace OffloadProject\Navigation\Contracts;

interface NavigationInterface
{
    /**
     * Build the navigation tree for rendering.
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
     * @param  array<string, mixed>  $routeParams
     * @return array<int, array<string, mixed>>
     */
    public function getBreadcrumbs(string $currentRouteName, array $routeParams = []): array;
}
