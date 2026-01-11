<?php

declare(strict_types=1);

use OffloadProject\Navigation\Facades\Navigation;
use OffloadProject\Navigation\Item;
use OffloadProject\Navigation\NavigationBuilder;

describe('Runtime registration API', function (): void {
    beforeEach(function (): void {
        Navigation::clearAll();
    });

    it('registers navigation using register() fluent builder', function (): void {
        Navigation::register('main')
            ->item('Dashboard', 'dashboard', 'home')
            ->item('Users', 'users.index', 'users')
            ->done();

        expect(Navigation::has('main'))->toBeTrue();
    });

    it('register() returns NavigationBuilder', function (): void {
        $builder = Navigation::register('sidebar');

        expect($builder)->toBeInstanceOf(NavigationBuilder::class);
    });

    it('addNavigation() adds navigation from array', function (): void {
        Navigation::addNavigation('main', [
            Item::make('Dashboard')->route('dashboard')->icon('home'),
            Item::make('Users')->route('users.index')->icon('users'),
        ]);

        expect(Navigation::has('main'))->toBeTrue();
    });

    it('has() returns false for non-existent navigation', function (): void {
        expect(Navigation::has('nonexistent'))->toBeFalse();
    });

    it('names() returns all navigation names', function (): void {
        Navigation::register('main')->item('Dashboard', 'dashboard')->done();
        Navigation::register('sidebar')->item('Settings', 'settings')->done();

        $names = Navigation::names();

        expect($names)->toContain('main');
        expect($names)->toContain('sidebar');
    });

    it('clearAll() removes all navigations', function (): void {
        Navigation::register('main')->item('Dashboard', 'dashboard')->done();
        Navigation::register('sidebar')->item('Settings', 'settings')->done();

        Navigation::clearAll();

        expect(Navigation::has('main'))->toBeFalse();
        expect(Navigation::has('sidebar'))->toBeFalse();
    });

    it('supports external links in builder', function (): void {
        Navigation::register('main')
            ->item('Dashboard', 'dashboard')
            ->external('Docs', 'https://docs.example.com', 'book')
            ->done();

        expect(Navigation::has('main'))->toBeTrue();
    });

    it('supports action items in builder', function (): void {
        Navigation::register('main')
            ->item('Dashboard', 'dashboard')
            ->action('Logout', 'logout', 'post', 'log-out')
            ->done();

        expect(Navigation::has('main'))->toBeTrue();
    });

    it('supports separators in builder', function (): void {
        Navigation::register('main')
            ->item('Dashboard', 'dashboard')
            ->separator()
            ->item('Settings', 'settings')
            ->done();

        expect(Navigation::has('main'))->toBeTrue();
    });

    it('supports dividers in builder', function (): void {
        Navigation::register('main')
            ->item('Dashboard', 'dashboard')
            ->divider('large')
            ->item('Settings', 'settings')
            ->done();

        expect(Navigation::has('main'))->toBeTrue();
    });

    it('supports nested children with child()', function (): void {
        Navigation::register('main')
            ->item('Settings', 'settings', 'cog')
            ->child('Profile', 'settings.profile')
            ->child('Security', 'settings.security')
            ->item('Dashboard', 'dashboard')
            ->done();

        expect(Navigation::has('main'))->toBeTrue();
    });

    it('supports adding Item instances with add()', function (): void {
        Navigation::register('main')
            ->add(Item::make('Dashboard')->route('dashboard')->icon('home'))
            ->add(Item::make('Settings')->route('settings'))
            ->done();

        expect(Navigation::has('main'))->toBeTrue();
    });

    it('toArray() returns the navigation items', function (): void {
        $items = Navigation::register('main')
            ->item('Dashboard', 'dashboard', 'home')
            ->toArray();

        expect($items)->toHaveCount(1);
        expect($items[0]['label'])->toBe('Dashboard');
    });

    it('get() returns the built Navigation instance', function (): void {
        $nav = Navigation::register('main')
            ->item('Dashboard', 'dashboard')
            ->get();

        expect($nav)->toBeInstanceOf(OffloadProject\Navigation\Navigation::class);
    });

    it('can add items from array with addNavigation', function (): void {
        Navigation::addNavigation('main', [
            ['Dashboard', 'dashboard', 'home'],
            ['Users', 'users.index', 'users'],
        ]);

        expect(Navigation::has('main'))->toBeTrue();
    });

    it('can mix Item instances and arrays in addNavigation', function (): void {
        Navigation::addNavigation('main', [
            Item::make('Dashboard')->route('dashboard'),
            ['Users', 'users.index'],
        ]);

        expect(Navigation::has('main'))->toBeTrue();
    });
});
