---
name: Laravel Navigation
description: Conventions and APIs for the offload-project/laravel-navigation package — fluent builder, item kinds (item/group/section/separator/divider/external/action), breadcrumbs, active state, and icon compilation.
compatible_agents:
  - Claude Code
  - Cursor
tags:
  - laravel
  - php
  - navigation
  - breadcrumbs
  - sidebar
  - menu
  - inertia
---

## Context

`offload-project/laravel-navigation` is a Laravel 11/12/13 package (PHP 8.3+) for defining one or more named navigation structures and rendering them as a frontend-ready array. It ships:

- A fluent `Item` builder (alias of `ItemBuilder`) that produces items, groups, sections, separators, dividers, external links, and POST/DELETE actions.
- Global helper functions: `nav_item`, `nav_group`, `nav_section`, `nav_separator`, `nav_divider`, `nav_external`, `nav_action`.
- A `Navigation` facade backed by `NavigationManager` for retrieving items, generating breadcrumbs, and registering navigations at runtime.
- Auto breadcrumb generation with wildcard parameter support (`params(['user' => '*'])`) and `breadcrumbOnly` / `navOnly` visibility flags.
- Authorization via `can()` (gates / policies) and arbitrary visibility logic via `visible()` (closures, booleans).
- A `navigation:compile-icons` Artisan command that pre-compiles Lucide icons to inline SVG (cached as JSON, with legacy PHP loader retained).
- A `navigation:validate` Artisan command that fails if any item references a route name that doesn't exist.
- A `WayfinderAdapter` for projects using `laravel/wayfinder` so route references can come from typed Wayfinder objects.

Apply this skill when working in a Laravel app that has `offload-project/laravel-navigation` in `composer.json`, or when the user asks for help with the `Navigation` facade, the `Item` / `ItemBuilder` API, the `nav_*` helpers, breadcrumbs, sections, groups, or icon compilation in this package.

## Rules

### Choosing an API

1. Prefer the fluent `Item::make(...)` / `Item::group(...)` / `Item::section(...)` builder for items defined in PHP — full IDE autocomplete and type checking. Use the `nav_*` helpers for the most concise config-file syntax. Use plain arrays (`['label' => ..., 'route' => ...]` or shorthand `['Dashboard', 'dashboard', 'home']`) only when the data is coming from a source you can't make builder calls from (e.g., JSON imported at runtime).
2. Register navigations either statically in `config/navigation.php` (`'navigations' => [...]`) or at runtime via `Navigation::register('name')->...->done()` / `Navigation::addNavigation('name', [...])` in a service provider or middleware. Don't mutate the manager's internal arrays directly.
3. Get items via `Navigation::get($name)->items($params?)` and breadcrumbs via `Navigation::breadcrumbs($name?, $routeName?, $params?)`. These two are the public output contract — the array shape they return is what the frontend consumes.

### Items, groups, and sections

4. Use **items** for individual links (internal route, external URL, or action). Use **groups** for collapsible sub-menus (default `collapsible: true`). Use **sections** for top-level structural containers like `WORKSPACE`, `ADMIN` that hold items and groups (default `collapsible: false`).
5. Sections are top-level only. Nesting a section inside another section or inside a group throws `InvalidNavigationItemException`. Groups can be nested inside groups; items can live inside either.
6. Set `route` OR `url`, not both — passing both throws. Use `route` for internal Laravel route names; use `url` (or `Item::external(...)`) for external links. Groups and sections do not have a URL.
7. Use `Item::action(...)` (or `nav_action(...)`) for POST/DELETE items like Logout. The frontend reads the `method` key and submits a form / `router.post()` rather than navigating.

### Authorization vs visibility

8. Use `->can(...)` for authorization checks — it routes through Laravel's gate. Pass a single ability (`->can('access-admin')`) or a `[ability, model]` array (`->can(['update', $post])`).
9. Use `->visible(...)` for non-authorization conditions — feature flags, environment checks, anything that isn't a gate. Accepts a boolean or a closure. Don't squeeze feature flags through `can()`.
10. Prefer the `whenAuthenticated()` / `whenGuest()` shortcuts over hand-rolling `->visible(fn () => auth()->check())` when the only requirement is auth state.

### Breadcrumbs

11. Generate breadcrumbs with `Navigation::breadcrumbs($name?, $routeName?, $params?)`. With no arguments it auto-detects the current route across all registered navigations. The last breadcrumb in the array has `isActive => true`.
12. For CRUD detail pages (`/users/{user}/edit`) that shouldn't appear in the sidebar but should appear in breadcrumbs, set `->breadcrumbOnly()` and use wildcard params (`->params(['user' => '*'])`). The closure label receives the resolved route model: `Item::make(fn ($user) => "Edit: {$user->name}")`.
13. For headers that should appear in the sidebar but not in breadcrumbs, set `->navOnly()`.

### Metadata, badges, custom keys

14. Use `->badge($value, $color?)` for simple count badges. `$value` can be an int, string, or closure. Use `->meta('key', $value)` for any other arbitrary frontend data. Custom keys pass through unchanged to the frontend.
15. The output array always contains at least: `id`, `label`, `url`, `isActive`, `icon`, `children`. Groups add `group => true`, `collapsible`, `collapsed`. Sections add `section => true`, `collapsible`, `collapsed`. Treat anything else as opaque app metadata.

### Icons

16. Use Lucide icon names as strings (`'home'`, `'users'`, `'log-out'`). The icon string is compiled to inline SVG by `IconCompiler` (sanitized via `enshrined/svg-sanitize`). Don't pass raw SVG markup as the icon name.
17. Run `php artisan navigation:compile-icons` as part of deployment. Compiled icons live at `config('navigation.icons.compiled_path')` (default `storage/navigation/icons.json`). The legacy `.php` format is still loaded if present, but new compilations write JSON — don't reach in and edit the file by hand.

### Route validation

18. Add `php artisan navigation:validate` to CI to catch references to deleted or renamed route names. Closure labels are validated by checking that the route exists — the closure itself isn't invoked.

### Runtime registration

19. When registering at runtime, end the fluent chain with `->done()` so the navigation is committed to the manager. Forgetting `->done()` leaves the builder unflushed.
20. `Navigation::clearCache()` clears resolved item caches without dropping registrations. `Navigation::clearAll()` drops everything — use it in tests, not at runtime.

### Wayfinder

21. When `laravel/wayfinder` is installed, you can pass Wayfinder route references through `WayfinderAdapter` instead of string route names. Don't try to mix Wayfinder objects into the shorthand array syntax — use the builder.

## Examples

### Basic config-file navigation

```php
// config/navigation.php
use OffloadProject\Navigation\Item;

return [
    'navigations' => [
        'main' => [
            Item::make('Dashboard')->route('dashboard')->icon('home'),
            Item::make('Users')
                ->route('users.index')
                ->icon('users')
                ->can('view-users')
                ->children([
                    Item::make('All Users')->route('users.index'),
                    Item::make('Roles')->route('roles.index'),
                ]),
            Item::make('Settings')->route('settings')->icon('settings'),
        ],
    ],
];
```

### Sidebar with sections and groups

```php
use OffloadProject\Navigation\Item;

return [
    'navigations' => [
        'sidebar' => [
            Item::section('Workspace', [
                Item::make('Dashboard')->route('dashboard')->icon('home'),
                Item::group('Settings', [
                    Item::make('Profile')->route('settings.profile'),
                    Item::make('Security')->route('settings.security'),
                ])->icon('cog'),
            ])->icon('layers'),

            Item::section('Admin', [
                Item::make('Users')->route('admin.users'),
                Item::make('Roles')->route('admin.roles'),
            ])->icon('shield')->can('access-admin'),
        ],
    ],
];
```

### Helpers for terse config

```php
return [
    'navigations' => [
        'main' => [
            nav_item('Dashboard', 'dashboard', 'home'),
            nav_section('Workspace', [
                nav_item('Projects', 'projects.index', 'folder'),
                nav_group('Settings', [
                    nav_item('Profile', 'settings.profile'),
                    nav_item('Security', 'settings.security'),
                ], 'cog'),
            ], 'layers'),
            nav_separator(),
            nav_external('Documentation', 'https://docs.example.com', 'book'),
            nav_action('Logout', 'logout', 'post', 'log-out'),
        ],
    ],
];
```

### Runtime registration in a service provider

```php
use OffloadProject\Navigation\Facades\Navigation;
use OffloadProject\Navigation\Item;

public function boot(): void
{
    Navigation::register('sidebar')
        ->item('Dashboard', 'dashboard', 'home')
        ->item('Users', 'users.index', 'users')
            ->child('All Users', 'users.index')
            ->child('Create User', 'users.create')
        ->separator()
        ->section('Admin', [
            Item::make('Roles')->route('admin.roles'),
        ], 'shield')
        ->done(); // ← required
}
```

### Sharing navigation with Inertia

```php
// app/Http/Middleware/HandleInertiaRequests.php
use OffloadProject\Navigation\Facades\Navigation;

public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'navigation' => Navigation::get('main')->items(),
        'breadcrumbs' => Navigation::breadcrumbs('main'),
    ];
}
```

### Breadcrumbs for a CRUD detail page

```php
use OffloadProject\Navigation\Item;

Item::make('Users')
    ->route('users.index')
    ->icon('users')
    ->children([
        Item::make(fn ($user) => "Edit: {$user->name}")
            ->route('users.edit')
            ->params(['user' => '*'])
            ->breadcrumbOnly(),
    ]),

// On /users/5/edit:
// - sidebar shows "Users"
// - breadcrumbs show "Users > Edit: John Doe"
// - "Users" is marked active
```

### Authorization vs visibility

```php
// Gate-based — use can()
Item::make('Admin')->route('admin.index')->can('access-admin');
Item::make('Edit Post')->route('posts.edit')->can(['update', $post]);

// Feature flag / env — use visible()
Item::make('Beta Features')->route('beta')->visible(config('features.beta'));
Item::make('Debug')->route('debug')->visible(fn () => app()->isLocal());

// Auth state shortcuts
Item::make('Login')->route('login')->whenGuest();
Item::make('Logout')->route('logout')->whenAuthenticated();
```

### Badges and custom metadata

```php
Item::make('Notifications')
    ->route('notifications')
    ->icon('bell')
    ->badge(fn () => auth()->user()->unreadCount(), 'red')
    ->meta('feature', 'beta');
```

### Compile icons in deploy + validate in CI

```yaml
# Deploy step
- run: php artisan navigation:compile-icons

# CI step (fails the build if a route name is missing)
- run: php artisan navigation:validate
```

## Anti-patterns

- ❌ Setting both `route` and `url` on the same item — throws. Pick one. Use `Item::external(...)` for external links.
- ❌ Nesting `Item::section(...)` inside another section or inside a group — throws `InvalidNavigationItemException`. Sections are top-level only.
- ❌ Squeezing feature flags through `->can()`. `can` is for gates/policies; `visible` is for everything else.
- ❌ Forgetting `->done()` on `Navigation::register('name')->...` — the navigation never gets committed to the manager.
- ❌ Passing raw SVG markup as the `icon` argument. The package compiles a Lucide icon **name** (e.g., `'home'`) into sanitized SVG; raw markup will be treated as a name and fail.
- ❌ Calling `Item::make(...)` from a config file without importing the class. Either `use OffloadProject\Navigation\Item;` at the top, or use the `nav_*` helpers (which work without an import because they live in the global namespace).
- ❌ Using deprecated `->toTree()` / `->getBreadcrumbs()` methods. Use `->items()` and `->breadcrumbs()` — the old names will be removed in v2.0.
- ❌ Reading or editing `storage/navigation/icons.json` (or `.php`) by hand. Always rebuild via `php artisan navigation:compile-icons`.
- ❌ Calling `Navigation::clearAll()` at runtime to "reset" something. It drops every registration; reach for it only in tests.
- ❌ Editing files inside `vendor/offload-project/laravel-navigation`. All extension points (config, runtime registration, icon path, custom meta, visibility/can closures) are exposed through the public API.

## References

- Repository: <https://github.com/offload-project/laravel-navigation>
- README — full usage reference: <https://github.com/offload-project/laravel-navigation/blob/main/README.md>
- Changelog: <https://github.com/offload-project/laravel-navigation/blob/main/CHANGELOG.md>
- Laravel Boost skills directory: <https://skills.laravel.cloud/>
