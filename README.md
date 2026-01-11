<p align="center">
    <a href="https://packagist.org/packages/offload-project/laravel-navigation"><img src="https://img.shields.io/packagist/v/offload-project/laravel-navigation.svg?style=flat-square" alt="Latest Version on Packagist"></a>
    <a href="https://github.com/offload-project/laravel-navigation/actions"><img src="https://img.shields.io/github/actions/workflow/status/offload-project/laravel-navigation/tests.yml?branch=main&style=flat-square" alt="GitHub Tests Action Status"></a>
    <a href="https://packagist.org/packages/offload-project/laravel-navigation"><img src="https://img.shields.io/packagist/dt/offload-project/laravel-navigation.svg?style=flat-square" alt="Total Downloads"></a>
</p>

# Laravel Navigation

A powerful, flexible navigation management package for Laravel. Define multiple navigation structures with breadcrumbs,
active state detection, and pre-compiled icons — perfect for Inertia.js, React, Vue, and Blade applications.

## Features

- **Multiple Navigations** — Define unlimited nav structures (main, footer, sidebar, user menu)
- **Fluent Builder API** — IDE-friendly builder with full autocomplete support
- **Route-Based** — Use Laravel route names with full IDE autocomplete
- **Breadcrumb Generation** — Auto-generate breadcrumbs from your navigation config
- **Active State Detection** — Smart detection of active items and their parents
- **Authorization** — Built-in `can` and `visible` attributes for permissions
- **Pre-compiled Icons** — Compile Lucide icons to inline SVG for optimal performance
- **Action Support** — POST/DELETE actions for logout, form submissions, etc.
- **Wildcard Parameters** — Match dynamic routes like `/users/{id}/edit` in breadcrumbs
- **Custom Metadata** — Attach badges, feature flags, or any data to nav items

## Requirements

- PHP 8.3+
- Laravel 11.0+

## Installation

```bash
composer require offload-project/laravel-navigation
```

Optionally publish the configuration:

```bash
php artisan vendor:publish --tag=navigation-config
```

## Quick Start

### Using the Fluent Builder (Recommended)

The fluent builder provides full IDE autocomplete and a clean, readable syntax:

```php
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

### Using Helper Functions

Global helper functions provide the most concise syntax:

```php
return [
    'navigations' => [
        'main' => [
            nav_item('Dashboard', 'dashboard', 'home'),
            nav_item('Users', 'users.index', 'users')
                ->can('view-users')
                ->children([
                    nav_item('All Users', 'users.index'),
                    nav_item('Roles', 'roles.index'),
                ]),
            nav_separator(),
            nav_item('Settings', 'settings', 'settings'),
            nav_external('Documentation', 'https://docs.example.com', 'book'),
            nav_action('Logout', 'logout', 'post', 'log-out'),
        ],
    ],
];
```

Available helpers:
- `nav_item($label, $route?, $icon?)` — Standard navigation item
- `nav_group($label, $children?, $icon?)` — Collapsible group/section
- `nav_separator()` — Visual separator
- `nav_divider($spacing?)` — Divider with optional spacing
- `nav_external($label, $url, $icon?)` — External link
- `nav_action($label, $route, $method?, $icon?)` — POST/DELETE action

### Using Shorthand Syntax

For quick configuration, use the shorthand array syntax:

```php
return [
    'navigations' => [
        'main' => [
            ['Dashboard', 'dashboard', 'home'],           // [label, route, icon]
            ['Users', 'users.index', 'users'],            // [label, route, icon]
            ['Docs', 'https://docs.example.com', 'book'], // URLs auto-detected
        ],
    ],
];
```

### Using Array Syntax

The traditional array syntax is still fully supported:

```php
return [
    'navigations' => [
        'main' => [
            ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home'],
            [
                'label' => 'Users',
                'route' => 'users.index',
                'icon' => 'users',
                'can' => 'view-users',
                'children' => [
                    ['label' => 'All Users', 'route' => 'users.index'],
                    ['label' => 'Roles', 'route' => 'roles.index'],
                ],
            ],
        ],
    ],
];
```

## Runtime Registration

Register navigations at runtime in your service provider or middleware:

```php
use OffloadProject\Navigation\Facades\Navigation;
use OffloadProject\Navigation\Item;

// Fluent builder
Navigation::register('sidebar')
    ->item('Dashboard', 'dashboard', 'home')
    ->item('Users', 'users.index', 'users')
        ->child('All Users', 'users.index')
        ->child('Create User', 'users.create')
    ->separator()
    ->item('Settings', 'settings', 'settings')
    ->done();

// Or with Item instances
Navigation::addNavigation('sidebar', [
    Item::make('Dashboard')->route('dashboard')->icon('home'),
    Item::make('Users')->route('users.index')->icon('users'),
]);
```

## Getting Navigation Data

```php
use OffloadProject\Navigation\Facades\Navigation;

// Get navigation items
$items = Navigation::get('main')->items();

// Get breadcrumbs (auto-detects current route)
$breadcrumbs = Navigation::breadcrumbs('main');

// Check if navigation exists
if (Navigation::has('sidebar')) {
    // ...
}

// Get all navigation names
$names = Navigation::names();
```

Pass to your frontend:

```php
// Inertia.js
return inertia('Dashboard', [
    'navigation' => Navigation::get('main')->items(),
    'breadcrumbs' => Navigation::breadcrumbs('main'),
]);
```

## Fluent Builder API

The `Item` class provides a fluent interface with full IDE support:

```php
use OffloadProject\Navigation\Item;

// Basic item
Item::make('Dashboard')->route('dashboard')->icon('home')

// Shorthand with route and icon
Item::to('Dashboard', 'dashboard', 'home')

// External link
Item::external('Documentation', 'https://docs.example.com', 'book')

// Action (POST/DELETE)
Item::action('Logout', 'logout', 'post', 'log-out')

// Separator
Item::separator()

// Divider with spacing
Item::divider('large')

// With children
Item::make('Settings')
    ->route('settings')
    ->icon('settings')
    ->children([
        Item::make('Profile')->route('settings.profile'),
        Item::make('Security')->route('settings.security'),
    ])

// With authorization
Item::make('Admin')
    ->route('admin')
    ->can('access-admin')

// With visibility
Item::make('Dashboard')
    ->route('dashboard')
    ->visible(fn () => auth()->check())
    ->whenAuthenticated()  // Shorthand for authenticated users
    ->whenGuest()          // Shorthand for guests only

// With badge
Item::make('Notifications')
    ->route('notifications')
    ->badge(5)
    ->badge(fn () => auth()->user()->unreadCount(), 'red')

// For breadcrumbs only
Item::make(fn ($user) => "Edit: {$user->name}")
    ->route('users.edit')
    ->params(['user' => '*'])
    ->breadcrumbOnly()

// For navigation only
Item::make('Admin Section')
    ->route('admin')
    ->navOnly()

// Custom metadata
Item::make('Dashboard')
    ->route('dashboard')
    ->meta('badge', 5)
    ->meta('feature', 'beta')
```

## Groups & Sections

Organize navigation items into collapsible groups with headers:

```php
use OffloadProject\Navigation\Item;

return [
    'navigations' => [
        'sidebar' => [
            Item::make('Dashboard')->route('dashboard')->icon('home'),

            Item::group('Settings', [
                Item::make('Profile')->route('settings.profile'),
                Item::make('Security')->route('settings.security'),
                Item::make('Notifications')->route('settings.notifications'),
            ])->icon('cog'),

            Item::group('Administration', [
                Item::make('Users')->route('admin.users'),
                Item::make('Roles')->route('admin.roles'),
            ])->icon('shield')->collapsed(), // Starts collapsed
        ],
    ],
];
```

Or with helper functions:

```php
return [
    'navigations' => [
        'sidebar' => [
            nav_item('Dashboard', 'dashboard', 'home'),

            nav_group('Settings', [
                nav_item('Profile', 'settings.profile'),
                nav_item('Security', 'settings.security'),
            ], 'cog'),

            nav_group('Admin', [], 'shield')
                ->collapsed()
                ->can('access-admin')
                ->children([
                    nav_item('Users', 'admin.users'),
                    nav_item('Roles', 'admin.roles'),
                ]),
        ],
    ],
];
```

### Group Options

```php
// Basic group
Item::group('Settings', [...])

// With icon
Item::group('Settings', [...])->icon('cog')

// Not collapsible (always expanded)
Item::group('Main')->collapsible(false)

// Starts collapsed
Item::group('Advanced')->collapsed()

// With authorization
Item::group('Admin')->can('access-admin')

// With visibility
Item::group('Beta')->visible(config('features.beta'))

// Nested groups
Item::group('Settings', [
    Item::make('General')->route('settings.general'),
    Item::group('Advanced', [
        Item::make('API Keys')->route('settings.api'),
        Item::make('Webhooks')->route('settings.webhooks'),
    ])->collapsed(),
])
```

### Group Output

Groups output with these additional fields:

```php
[
    'id' => 'nav-sidebar-1',
    'label' => 'Settings',
    'url' => null,           // Groups don't have URLs
    'isActive' => true,      // Active if any child is active
    'icon' => '<svg>...</svg>',
    'group' => true,         // Identifies this as a group
    'collapsible' => true,   // Can be collapsed
    'collapsed' => false,    // Default collapsed state
    'children' => [...],
]
```

## Route Parameters

Pass parameters for routes that require them:

```php
// For routes like /users/{user}/posts
$items = Navigation::get('sidebar')->items(['user' => $user->id]);
```

## Authorization

Use the `can` attribute to check gates or policies:

```php
Item::make('Admin')
    ->route('admin.index')
    ->can('access-admin')

// With policy model
Item::make('Edit Post')
    ->route('posts.edit')
    ->can(['update', $post])
```

Items are automatically hidden when the user isn't authenticated or lacks permission.

For non-authorization logic (feature flags, environment checks), use `visible`:

```php
Item::make('Beta Features')
    ->route('beta')
    ->visible(config('features.beta'))

// Or with closures
Item::make('Debug')
    ->route('debug')
    ->visible(fn () => app()->isLocal())
```

## Action Items

Define items that trigger POST/DELETE requests:

```php
Item::action('Logout', 'logout', 'post', 'log-out')

// Or with array syntax
['label' => 'Logout', 'route' => 'logout', 'method' => 'post', 'icon' => 'log-out']
```

Handle in your frontend by checking for the `method` key and using a form or Inertia's `router.post()`.

## Breadcrumbs & Wildcards

Handle CRUD pages elegantly with `breadcrumbOnly` and wildcard parameters:

```php
Item::make('Users')
    ->route('users.index')
    ->children([
        Item::make(fn ($user) => "Edit: {$user->name}")
            ->route('users.edit')
            ->params(['user' => '*'])
            ->breadcrumbOnly(),
    ])
```

When visiting `/users/5/edit`:

- **Navigation** shows only "Users"
- **Breadcrumbs** show "Users > Edit: John Doe"
- **Active state** marks "Users" as active

### Breadcrumbs API

```php
// Auto-detect current route, search all navigations
$breadcrumbs = Navigation::breadcrumbs();

// Search specific navigation
$breadcrumbs = Navigation::breadcrumbs('main');

// Specify route explicitly
$breadcrumbs = Navigation::breadcrumbs('main', 'users.edit');

// With route parameters
$breadcrumbs = Navigation::breadcrumbs('main', 'users.edit', ['user' => $user]);
```

## Visibility Control

Use `navOnly` and `breadcrumbOnly` to control where items appear:

```php
Item::make('Admin Section')
    ->route('admin.index')
    ->navOnly()  // Shows in nav, excluded from breadcrumbs
    ->children([
        Item::make('Users')->route('admin.users'),
        Item::make(fn ($user) => "Edit {$user->name}")
            ->route('admin.users.edit')
            ->params(['user' => '*'])
            ->breadcrumbOnly(),  // Shows in breadcrumbs, excluded from nav
    ])
```

- **`navOnly`** — Section headers that would be redundant in breadcrumbs
- **`breadcrumbOnly`** — Edit/show pages that shouldn't clutter navigation

## Custom Metadata

Attach any data to navigation items:

```php
Item::make('Notifications')
    ->route('notifications')
    ->badge(5)
    ->meta('badgeColor', 'red')
    ->meta('feature', 'new')

// Or with array syntax
[
    'label' => 'Notifications',
    'route' => 'notifications',
    'badge' => 5,
    'badgeColor' => 'red',
]
```

Custom keys pass through unchanged to your frontend.

## Icon Compilation

Pre-compile Lucide icons to inline SVG for optimal performance:

```bash
php artisan navigation:compile-icons
```

Add to your deployment pipeline for production builds.

## Route Validation

Validate all route references exist:

```bash
php artisan navigation:validate
```

Add to CI/CD to catch broken navigation links early.

## Inertia.js Integration

Share navigation globally via middleware:

```php
// app/Http/Middleware/HandleInertiaRequests.php
public function share(Request $request): array
{
    return [
        ...parent::share($request),
        'navigation' => Navigation::get('main')->items(),
        'breadcrumbs' => Navigation::breadcrumbs('main'),
    ];
}
```

## Output Format

The `items()` method returns a frontend-ready structure:

```php
[
    [
        'id' => 'nav-main-0',
        'label' => 'Dashboard',
        'url' => '/dashboard',
        'isActive' => true,
        'icon' => '<svg>...</svg>',
        'children' => [],
    ],
    // ...
]
```

## Configuration Reference

| Option           | Type              | Description                                      |
|------------------|-------------------|--------------------------------------------------|
| `label`          | `string\|Closure` | Display text (closures receive route models)     |
| `route`          | `string`          | Laravel route name                               |
| `url`            | `string`          | External URL (alternative to `route`)            |
| `method`         | `string`          | HTTP method (`post`, `delete`)                   |
| `icon`           | `string`          | Lucide icon name                                 |
| `children`       | `array`           | Nested navigation items                          |
| `visible`        | `bool\|Closure`   | Visibility condition                             |
| `can`            | `string\|array`   | Gate/policy check                                |
| `breadcrumbOnly` | `bool`            | Hide from nav, show in breadcrumbs               |
| `navOnly`        | `bool`            | Show in nav, hide from breadcrumbs               |
| `params`         | `array`           | Route parameters (`['id' => '*']` for wildcards) |

## Error Messages

The package provides helpful error messages when configuration is invalid:

```
Navigation item cannot have both "route" and "url". Use "route" for internal
Laravel routes (e.g., "users.index") or "url" for external links
(e.g., "https://docs.example.com"), but not both.
See: https://github.com/offload-project/laravel-navigation#routing
```

## Migration Guide

### Upgrading to v1.1

#### Breaking Changes

**Icon Storage Format Changed**

Compiled icons are now stored as JSON instead of PHP. If you have previously compiled icons:

```bash
# Recompile your icons to use the new format
php artisan navigation:compile-icons
```

The old PHP format (`storage/navigation/icons.php`) will still be loaded for backwards compatibility, but new compilations will use JSON (`storage/navigation/icons.json`).

**NavigationManager Constructor**

If you're manually instantiating `NavigationManager`, the constructor now requires an `ItemVisibilityResolver` instance:

```php
// Before
new NavigationManager($config, $iconCompiler);

// After
new NavigationManager($config, $iconCompiler, $visibilityResolver);
```

Most users won't be affected as the class is typically resolved from the container.

### Deprecated Methods

The following methods are deprecated and will be removed in v2.0:

| Deprecated | Use Instead |
|------------|-------------|
| `->toTree()` | `->items()` |
| `->getBreadcrumbs()` | `->breadcrumbs()` |

## Testing

```bash
./vendor/bin/pest
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
