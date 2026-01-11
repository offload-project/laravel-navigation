<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OffloadProject\Navigation\Facades\Navigation;
use OffloadProject\Navigation\Item;

describe('Navigation Groups', function (): void {
    beforeEach(function (): void {
        Route::get('/dashboard', fn () => 'dashboard')->name('dashboard');
        Route::get('/settings/profile', fn () => 'profile')->name('settings.profile');
        Route::get('/settings/security', fn () => 'security')->name('settings.security');
        Route::get('/admin/users', fn () => 'users')->name('admin.users');
        Route::get('/admin/roles', fn () => 'roles')->name('admin.roles');

        Navigation::clearAll();
    });

    it('creates a basic group with children', function (): void {
        $group = Item::group('Settings', [
            Item::make('Profile')->route('settings.profile'),
            Item::make('Security')->route('settings.security'),
        ])->toArray();

        expect($group)
            ->toHaveKey('label', 'Settings')
            ->toHaveKey('group', true)
            ->toHaveKey('collapsible', true)
            ->toHaveKey('collapsed', false)
            ->toHaveKey('children');

        expect($group['children'])->toHaveCount(2);
    });

    it('creates a group without initial children', function (): void {
        $group = Item::group('Settings')
            ->children([
                Item::make('Profile')->route('settings.profile'),
            ])
            ->toArray();

        expect($group)
            ->toHaveKey('label', 'Settings')
            ->toHaveKey('group', true)
            ->toHaveKey('children');

        expect($group['children'])->toHaveCount(1);
    });

    it('can set group as collapsed by default', function (): void {
        $group = Item::group('Advanced')
            ->collapsed()
            ->toArray();

        expect($group)
            ->toHaveKey('collapsed', true)
            ->toHaveKey('collapsible', true);
    });

    it('can set group as not collapsible', function (): void {
        $group = Item::group('Main')
            ->collapsible(false)
            ->toArray();

        expect($group)->toHaveKey('collapsible', false);
    });

    it('can add icon to group', function (): void {
        $group = Item::group('Settings')
            ->icon('cog')
            ->toArray();

        expect($group)->toHaveKey('icon', 'cog');
    });

    it('can add visibility to group', function (): void {
        $group = Item::group('Admin')
            ->can('access-admin')
            ->toArray();

        expect($group)->toHaveKey('can', 'access-admin');
    });

    it('groups work in navigation config', function (): void {
        Navigation::addNavigation('main', [
            Item::make('Dashboard')->route('dashboard'),
            Item::group('Settings', [
                Item::make('Profile')->route('settings.profile'),
                Item::make('Security')->route('settings.security'),
            ])->icon('cog'),
        ]);

        $items = Navigation::get('main')->items();

        expect($items)->toHaveCount(2);
        expect($items[0]['label'])->toBe('Dashboard');
        expect($items[1]['label'])->toBe('Settings');
        expect($items[1]['group'])->toBeTrue();
        expect($items[1]['children'])->toHaveCount(2);
    });

    it('nested groups work correctly', function (): void {
        $group = Item::group('Admin', [
            Item::make('Dashboard')->route('dashboard'),
            Item::group('User Management', [
                Item::make('Users')->route('admin.users'),
                Item::make('Roles')->route('admin.roles'),
            ]),
        ])->toArray();

        expect($group['children'])->toHaveCount(2);
        expect($group['children'][1]['group'])->toBeTrue();
        expect($group['children'][1]['children'])->toHaveCount(2);
    });

    it('group active state bubbles up from children', function (): void {
        Navigation::addNavigation('main', [
            Item::group('Settings', [
                Item::make('Profile')->route('settings.profile'),
                Item::make('Security')->route('settings.security'),
            ]),
        ]);

        // Simulate being on the profile page
        $this->get('/settings/profile');

        $items = Navigation::get('main')->items();

        expect($items[0]['isActive'])->toBeTrue();
        expect($items[0]['children'][0]['isActive'])->toBeTrue();
        expect($items[0]['children'][1]['isActive'])->toBeFalse();
    });

    it('nav_group helper creates group', function (): void {
        $group = nav_group('Settings', [
            nav_item('Profile', 'settings.profile'),
        ])->toArray();

        expect($group)
            ->toHaveKey('label', 'Settings')
            ->toHaveKey('group', true)
            ->toHaveKey('children');
    });

    it('nav_group helper accepts icon', function (): void {
        $group = nav_group('Settings', [], 'cog')->toArray();

        expect($group)
            ->toHaveKey('icon', 'cog')
            ->toHaveKey('group', true);
    });

    it('nav_group helper supports chaining', function (): void {
        $group = nav_group('Admin')
            ->icon('shield')
            ->collapsed()
            ->can('admin')
            ->children([
                nav_item('Users', 'admin.users'),
            ])
            ->toArray();

        expect($group)
            ->toHaveKey('label', 'Admin')
            ->toHaveKey('icon', 'shield')
            ->toHaveKey('collapsed', true)
            ->toHaveKey('can', 'admin')
            ->toHaveKey('children');
    });

    it('groups render in navigation output', function (): void {
        Navigation::addNavigation('sidebar', [
            nav_group('Settings', [
                nav_item('Profile', 'settings.profile'),
                nav_item('Security', 'settings.security'),
            ], 'cog')->collapsed(),
        ]);

        $items = Navigation::get('sidebar')->items();

        expect($items[0])
            ->toHaveKey('label', 'Settings')
            ->toHaveKey('group', true)
            ->toHaveKey('collapsible', true)
            ->toHaveKey('collapsed', true)
            ->toHaveKey('children');
    });

    it('groups without routes have null url', function (): void {
        Navigation::addNavigation('main', [
            Item::group('Settings', [
                Item::make('Profile')->route('settings.profile'),
            ]),
        ]);

        $items = Navigation::get('main')->items();

        expect($items[0]['url'])->toBeNull();
    });

    it('groups can have badges', function (): void {
        $group = Item::group('Notifications')
            ->badge(5)
            ->toArray();

        expect($group)
            ->toHaveKey('badge', 5)
            ->toHaveKey('badgeColor', 'default');
    });

    it('groups can have custom meta', function (): void {
        $group = Item::group('Beta Features')
            ->meta('beta', true)
            ->meta('releaseDate', '2024-01-01')
            ->toArray();

        expect($group)
            ->toHaveKey('beta', true)
            ->toHaveKey('releaseDate', '2024-01-01');
    });
});
