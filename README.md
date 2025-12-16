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

**1. Define your navigation** in `config/navigation.php`:

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
            ['label' => 'Settings', 'route' => 'settings', 'icon' => 'settings'],
        ],
    ],
];
```

**2. Get navigation in your controller or middleware:**

```php
use OffloadProject\Navigation\Facades\Navigation;

// Get navigation tree
$nav = Navigation::get('main')->toTree();

// Get breadcrumbs (auto-detects current route)
$breadcrumbs = Navigation::breadcrumbs('main');
```

**3. Pass to your frontend:**

```php
// Inertia.js
return inertia('Dashboard', [
    'navigation' => Navigation::get('main')->toTree(),
    'breadcrumbs' => Navigation::breadcrumbs('main'),
]);
```

## Route Parameters

Pass parameters for routes that require them:

```php
// For routes like /users/{user}/posts
$nav = Navigation::get('sidebar')->toTree(['user' => $user->id]);
```

## Authorization

Use the `can` attribute to check gates or policies:

```php
[
    'label' => 'Admin',
    'route' => 'admin.index',
    'can' => 'access-admin',
]

// With policy model
[
    'label' => 'Edit Post',
    'route' => 'posts.edit',
    'can' => ['update', $post],
]
```

Items are automatically hidden when the user isn't authenticated or lacks permission.

For non-authorization logic (feature flags, environment checks), use `visible`:

```php
['label' => 'Beta', 'route' => 'beta', 'visible' => config('features.beta')]
```

## Action Items

Define items that trigger POST/DELETE requests:

```php
[
    'label' => 'Logout',
    'route' => 'logout',
    'method' => 'post',
    'icon' => 'log-out',
]
```

Handle in your frontend by checking for the `method` key and using a form or Inertia's `router.post()`.

## Breadcrumbs & Wildcards

Handle CRUD pages elegantly with `breadcrumbOnly` and wildcard parameters:

```php
[
    'label' => 'Users',
    'route' => 'users.index',
    'children' => [
        [
            'label' => fn($user) => "Edit: {$user->name}",
            'route' => 'users.edit',
            'breadcrumbOnly' => true,
            'params' => ['user' => '*'],
        ],
    ],
]
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
[
    'label' => 'Admin Section',
    'route' => 'admin.index',
    'navOnly' => true,  // Shows in nav, excluded from breadcrumbs
    'children' => [
        ['label' => 'Users', 'route' => 'admin.users'],
        [
            'label' => fn($user) => "Edit {$user->name}",
            'route' => 'admin.users.edit',
            'breadcrumbOnly' => true,  // Shows in breadcrumbs, excluded from nav
            'params' => ['user' => '*'],
        ],
    ],
]
```

- **`navOnly`** — Section headers that would be redundant in breadcrumbs
- **`breadcrumbOnly`** — Edit/show pages that shouldn't clutter navigation

## Custom Metadata

Attach any data to navigation items with `meta`:

```php
[
    'label' => 'Notifications',
    'route' => 'notifications',
    'meta' => ['badge' => 5, 'badgeColor' => 'red'],
]
```

The `meta` array passes through unchanged to your frontend.

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
        'navigation' => Navigation::get('main')->toTree(),
        'breadcrumbs' => Navigation::breadcrumbs('main'),
    ];
}
```

## Output Format

The `toTree()` method returns a frontend-ready structure:

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
| `meta`           | `array`           | Custom metadata passed to frontend               |

## Testing

```bash
./vendor/bin/pest
```

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
