<?php

declare(strict_types=1);

namespace OffloadProject\Navigation;

use OffloadProject\Navigation\Contracts\IconCompilerInterface;
use OffloadProject\Navigation\Support\ItemVisibilityResolver;

final class NavigationManager
{
    /** @var array<string, mixed> */
    private array $config;

    private IconCompilerInterface $iconCompiler;

    private ItemVisibilityResolver $visibilityResolver;

    /** @var array<string, Navigation> */
    private array $navigations = [];

    /** @var array<string, array<int, array<string, mixed>>> Runtime-registered navigations */
    private array $runtimeNavigations = [];

    /**
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        array $config,
        IconCompilerInterface $iconCompiler,
        ItemVisibilityResolver $visibilityResolver
    ) {
        $this->config = $config;
        $this->iconCompiler = $iconCompiler;
        $this->visibilityResolver = $visibilityResolver;
    }

    /**
     * Register a new navigation at runtime using fluent builder.
     *
     * @example
     * Navigation::register('sidebar')
     *     ->item('Dashboard', 'dashboard', 'home')
     *     ->item('Users', 'users.index', 'users')
     *         ->child('All Users', 'users.index')
     *         ->child('Roles', 'roles.index')
     *     ->done();
     */
    public function register(string $name): NavigationBuilder
    {
        return new NavigationBuilder($name, $this);
    }

    /**
     * Add a navigation from array configuration at runtime.
     *
     * @param  array<int, array<string, mixed>|ItemBuilder>  $items
     */
    public function addNavigation(string $name, array $items): self
    {
        // Convert any ItemBuilder instances to arrays
        $items = array_map(
            fn ($item) => $item instanceof ItemBuilder ? $item->toArray() : $item,
            $items
        );

        $this->runtimeNavigations[$name] = $items;

        // Clear cached navigation instance if it exists
        unset($this->navigations[$name]);

        return $this;
    }

    /**
     * Check if a navigation exists.
     */
    public function has(string $name): bool
    {
        return isset($this->runtimeNavigations[$name])
            || isset($this->config['navigations'][$name]);
    }

    public function get(string $name): Navigation
    {
        if (! isset($this->navigations[$name])) {
            // Runtime registrations take precedence
            $items = $this->runtimeNavigations[$name]
                ?? $this->config['navigations'][$name]
                ?? [];

            $this->navigations[$name] = new Navigation(
                $name,
                $items,
                $this->iconCompiler,
                $this->visibilityResolver
            );
        }

        return $this->navigations[$name];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function breadcrumbs(?string $name = null, ?string $routeName = null): array
    {
        $routeName = $routeName ?? request()->route()?->getName();

        if (! $routeName) {
            return [];
        }

        // Get current route parameters
        $routeParams = request()->route()?->parameters() ?? [];

        // If specific navigation provided, search only that one
        if ($name !== null) {
            $navigation = $this->get($name);

            return $navigation->getBreadcrumbs($routeName, $routeParams);
        }

        // Otherwise, search all navigations
        foreach ($this->config['navigations'] ?? [] as $navName => $items) {
            $navigation = $this->get($navName);
            $breadcrumbs = $navigation->getBreadcrumbs($routeName, $routeParams);

            if (! empty($breadcrumbs)) {
                return $breadcrumbs;
            }
        }

        return [];
    }

    /**
     * Get all registered navigation names.
     *
     * @return list<string>
     */
    public function getAllNavigations(): array
    {
        $configNames = array_keys($this->config['navigations'] ?? []);
        $runtimeNames = array_keys($this->runtimeNavigations);

        return array_values(array_unique([...$configNames, ...$runtimeNames]));
    }

    /**
     * Get all registered navigation names.
     *
     * @return list<string>
     */
    public function names(): array
    {
        return $this->getAllNavigations();
    }

    /**
     * Clear cached navigation instances.
     * Useful for testing or when config changes dynamically.
     */
    public function clearCache(): void
    {
        $this->navigations = [];
    }

    /**
     * Clear runtime navigations and cache.
     * Useful for testing.
     */
    public function clearAll(): void
    {
        $this->navigations = [];
        $this->runtimeNavigations = [];
    }
}
