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
    | Use the 'meta' key to store any custom data you want to pass to your frontend.
    |
    */

    'navigations' => [
        'main' => [
            [
                'label' => 'Dashboard',
                'route' => 'dashboard',
                'icon' => 'home',
            ],
            [
                'label' => 'Users',
                'route' => 'users.index',
                'icon' => 'users',
                'children' => [
                    ['label' => 'All Users', 'route' => 'users.index'],
                    ['label' => 'Roles', 'route' => 'users.roles.index'],
                    ['label' => 'Permissions', 'route' => 'users.permissions.index'],
                ],
            ],
            [
                'label' => 'Settings',
                'route' => 'settings.index',
                'icon' => 'settings',
            ],
        ],

        'user_menu' => [
            ['label' => 'Profile', 'route' => 'profile.edit', 'icon' => 'user'],
            ['label' => 'Settings', 'route' => 'settings.index', 'icon' => 'settings'],
            ['label' => 'Logout', 'route' => 'logout', 'method' => 'post', 'icon' => 'log-out'],
        ],

        'footer' => [
            ['label' => 'Documentation', 'url' => 'https://docs.example.com'],
            ['label' => 'Privacy Policy', 'route' => 'legal.privacy'],
            ['label' => 'Terms of Service', 'route' => 'legal.terms'],
        ],

        // Example with meta for custom frontend data
        'sidebar' => [
            [
                'label' => 'Dashboard',
                'route' => 'dashboard',
                'icon' => 'home',
                'meta' => ['badge' => 5, 'badgeColor' => 'red'],
            ],
            [
                'label' => 'Users',
                'route' => 'users.index',
                'icon' => 'users',
                'meta' => ['section' => 'admin'],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Icon Configuration
    |--------------------------------------------------------------------------
    |
    | Configure where compiled Lucide icons should be stored.
    |
    */

    'icons' => [
        'compiled_path' => storage_path('navigation/icons.php'),
    ],
];
