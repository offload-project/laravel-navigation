<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OffloadProject\Navigation\Exceptions\InvalidNavigationItemException;
use OffloadProject\Navigation\Facades\Navigation;
use OffloadProject\Navigation\Item;

describe('Navigation Sections', function (): void {
    beforeEach(function (): void {
        Route::get('/dashboard', fn () => 'dashboard')->name('dashboard');
        Route::get('/settings/profile', fn () => 'profile')->name('settings.profile');
        Route::get('/settings/security', fn () => 'security')->name('settings.security');
        Route::get('/admin/users', fn () => 'users')->name('admin.users');
        Route::get('/admin/roles', fn () => 'roles')->name('admin.roles');

        Navigation::clearAll();
    });

    it('creates a basic section with children', function (): void {
        $section = Item::section('Workspace', [
            Item::make('Dashboard')->route('dashboard'),
            Item::make('Profile')->route('settings.profile'),
        ])->toArray();

        expect($section)
            ->toHaveKey('label', 'Workspace')
            ->toHaveKey('section', true)
            ->toHaveKey('collapsible', false)
            ->toHaveKey('collapsed', false)
            ->toHaveKey('children');

        expect($section['children'])->toHaveCount(2);
    });

    it('creates a section without initial children', function (): void {
        $section = Item::section('Workspace')
            ->children([
                Item::make('Dashboard')->route('dashboard'),
            ])
            ->toArray();

        expect($section)
            ->toHaveKey('label', 'Workspace')
            ->toHaveKey('section', true);

        expect($section['children'])->toHaveCount(1);
    });

    it('section can hold both items and groups', function (): void {
        $section = Item::section('Workspace', [
            Item::make('Dashboard')->route('dashboard'),
            Item::group('Settings', [
                Item::make('Profile')->route('settings.profile'),
                Item::make('Security')->route('settings.security'),
            ]),
        ])->toArray();

        expect($section['children'])->toHaveCount(2);
        expect($section['children'][0])->toHaveKey('label', 'Dashboard');
        expect($section['children'][1])->toHaveKey('group', true);
        expect($section['children'][1]['children'])->toHaveCount(2);
    });

    it('section can be made collapsible', function (): void {
        $section = Item::section('Advanced')
            ->collapsible()
            ->collapsed()
            ->toArray();

        expect($section)
            ->toHaveKey('collapsible', true)
            ->toHaveKey('collapsed', true);
    });

    it('section can have an icon', function (): void {
        $section = Item::section('Workspace')
            ->icon('layers')
            ->toArray();

        expect($section)->toHaveKey('icon', 'layers');
    });

    it('section can have a gate check', function (): void {
        $section = Item::section('Admin')
            ->can('access-admin')
            ->toArray();

        expect($section)->toHaveKey('can', 'access-admin');
    });

    it('section can have badges and custom meta', function (): void {
        $section = Item::section('Notifications')
            ->badge(3)
            ->meta('beta', true)
            ->toArray();

        expect($section)
            ->toHaveKey('badge', 3)
            ->toHaveKey('badgeColor', 'default')
            ->toHaveKey('beta', true);
    });

    it('sections render in navigation output', function (): void {
        Navigation::addNavigation('sidebar', [
            Item::section('Workspace', [
                Item::make('Dashboard')->route('dashboard'),
                Item::group('Settings', [
                    Item::make('Profile')->route('settings.profile'),
                    Item::make('Security')->route('settings.security'),
                ]),
            ])->icon('layers'),
        ]);

        $items = Navigation::get('sidebar')->items();

        expect($items)->toHaveCount(1);
        expect($items[0])
            ->toHaveKey('label', 'Workspace')
            ->toHaveKey('section', true)
            ->toHaveKey('collapsible', false);

        expect($items[0]['children'])->toHaveCount(2);
        expect($items[0]['children'][0]['label'])->toBe('Dashboard');
        expect($items[0]['children'][1]['label'])->toBe('Settings');
        expect($items[0]['children'][1]['group'])->toBeTrue();
    });

    it('sections without routes do not have url in output', function (): void {
        Navigation::addNavigation('main', [
            Item::section('Workspace', [
                Item::make('Dashboard')->route('dashboard'),
            ]),
        ]);

        $items = Navigation::get('main')->items();

        expect($items[0])->not->toHaveKey('url');
    });

    it('section active state bubbles up from children', function (): void {
        Navigation::addNavigation('main', [
            Item::section('Workspace', [
                Item::make('Profile')->route('settings.profile'),
                Item::group('Admin', [
                    Item::make('Users')->route('admin.users'),
                ]),
            ]),
        ]);

        $this->get('/settings/profile');

        $items = Navigation::get('main')->items();

        expect($items[0]['isActive'])->toBeTrue();
        expect($items[0]['children'][0]['isActive'])->toBeTrue();
        expect($items[0]['children'][1]['isActive'])->toBeFalse();
    });

    it('nav_section helper creates a section', function (): void {
        $section = nav_section('Workspace', [
            nav_item('Dashboard', 'dashboard'),
        ])->toArray();

        expect($section)
            ->toHaveKey('label', 'Workspace')
            ->toHaveKey('section', true)
            ->toHaveKey('children');
    });

    it('nav_section helper accepts an icon', function (): void {
        $section = nav_section('Workspace', [], 'layers')->toArray();

        expect($section)
            ->toHaveKey('icon', 'layers')
            ->toHaveKey('section', true);
    });

    it('nav_section helper supports chaining', function (): void {
        $section = nav_section('Admin')
            ->icon('shield')
            ->can('admin')
            ->collapsible()
            ->children([
                nav_item('Users', 'admin.users'),
                nav_group('Roles', [
                    nav_item('All Roles', 'admin.roles'),
                ]),
            ])
            ->toArray();

        expect($section)
            ->toHaveKey('label', 'Admin')
            ->toHaveKey('icon', 'shield')
            ->toHaveKey('can', 'admin')
            ->toHaveKey('collapsible', true)
            ->toHaveKey('children');

        expect($section['children'])->toHaveCount(2);
    });

    it('NavigationBuilder::section adds a section', function (): void {
        Navigation::register('sidebar')
            ->section('Workspace', [
                Item::make('Dashboard')->route('dashboard'),
                Item::group('Settings', [
                    Item::make('Profile')->route('settings.profile'),
                ]),
            ])
            ->done();

        $items = Navigation::get('sidebar')->items();

        expect($items)->toHaveCount(1);
        expect($items[0])
            ->toHaveKey('label', 'Workspace')
            ->toHaveKey('section', true);
        expect($items[0]['children'])->toHaveCount(2);
        expect($items[0]['children'][1]['group'])->toBeTrue();
    });

    it('rejects sections nested inside other sections', function (): void {
        Navigation::addNavigation('main', [
            Item::section('Outer', [
                Item::section('Inner', [
                    Item::make('Profile')->route('settings.profile'),
                ]),
            ]),
        ]);

        expect(fn () => Navigation::get('main'))->toThrow(
            InvalidNavigationItemException::class,
            'Navigation sections cannot be nested inside a section.'
        );
    });

    it('rejects sections nested inside groups', function (): void {
        Navigation::addNavigation('main', [
            Item::group('Settings', [
                Item::section('Inner', [
                    Item::make('Profile')->route('settings.profile'),
                ]),
            ]),
        ]);

        expect(fn () => Navigation::get('main'))->toThrow(
            InvalidNavigationItemException::class,
            'Navigation sections cannot be nested inside a group.'
        );
    });
});
