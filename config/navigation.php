<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Navigation Definitions
    |--------------------------------------------------------------------------
    |
    | Define your application's navigation structures here. Each navigation
    | can contain items with routes, external URLs, icons, and nested children.
    |
    | You can use multiple formats:
    |
    | Full format:
    |   ['label' => 'Dashboard', 'route' => 'dashboard', 'icon' => 'home']
    |
    | Shorthand format:
    |   ['Dashboard', 'dashboard', 'home']  // [label, route, icon]
    |   ['Dashboard', 'dashboard']          // [label, route]
    |
    | Fluent builder (requires importing Item class):
    |   Item::make('Dashboard')->route('dashboard')->icon('home')
    |
    */

    'navigations' => [
        // 'main' => [
        //     ['Dashboard', 'dashboard', 'home'],
        //     ['Users', 'users.index', 'users'],
        //     ['Settings', 'settings.index', 'settings'],
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Icon Configuration
    |--------------------------------------------------------------------------
    |
    | Configure where compiled Lucide icons should be stored.
    | Run `php artisan navigation:compile-icons` to pre-compile icons.
    |
    */

    'icons' => [
        'compiled_path' => storage_path('navigation/icons.json'),
    ],
];
