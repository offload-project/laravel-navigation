<?php

declare(strict_types=1);

use OffloadProject\Navigation\Item;
use OffloadProject\Navigation\ItemBuilder;

describe('Item fluent builder', function (): void {
    it('creates basic item with make()', function (): void {
        $item = Item::make('Dashboard')->toArray();

        expect($item)->toHaveKey('label', 'Dashboard');
    });

    it('creates item with route', function (): void {
        $item = Item::make('Dashboard')
            ->route('dashboard')
            ->toArray();

        expect($item)
            ->toHaveKey('label', 'Dashboard')
            ->toHaveKey('route', 'dashboard');
    });

    it('creates item with route and icon', function (): void {
        $item = Item::make('Dashboard')
            ->route('dashboard')
            ->icon('home')
            ->toArray();

        expect($item)
            ->toHaveKey('label', 'Dashboard')
            ->toHaveKey('route', 'dashboard')
            ->toHaveKey('icon', 'home');
    });

    it('creates item using to() shorthand', function (): void {
        $item = Item::to('Dashboard', 'dashboard', 'home')->toArray();

        expect($item)
            ->toHaveKey('label', 'Dashboard')
            ->toHaveKey('route', 'dashboard')
            ->toHaveKey('icon', 'home');
    });

    it('creates external link', function (): void {
        $item = Item::external('Docs', 'https://docs.example.com', 'book')->toArray();

        expect($item)
            ->toHaveKey('label', 'Docs')
            ->toHaveKey('url', 'https://docs.example.com')
            ->toHaveKey('icon', 'book')
            ->not->toHaveKey('route');
    });

    it('creates action item', function (): void {
        $item = Item::action('Logout', 'logout', 'post', 'log-out')->toArray();

        expect($item)
            ->toHaveKey('label', 'Logout')
            ->toHaveKey('route', 'logout')
            ->toHaveKey('method', 'post')
            ->toHaveKey('icon', 'log-out');
    });

    it('creates separator', function (): void {
        $item = Item::separator()->toArray();

        expect($item)->toHaveKey('separator', true);
    });

    it('creates divider with default spacing', function (): void {
        $item = Item::divider()->toArray();

        expect($item)->toHaveKey('divider', 'default');
    });

    it('creates divider with custom spacing', function (): void {
        $item = Item::divider('large')->toArray();

        expect($item)->toHaveKey('divider', 'large');
    });

    it('adds children using array', function (): void {
        $item = Item::make('Settings')
            ->route('settings')
            ->children([
                Item::make('Profile')->route('settings.profile'),
                Item::make('Security')->route('settings.security'),
            ])
            ->toArray();

        expect($item)
            ->toHaveKey('label', 'Settings')
            ->toHaveKey('children')
            ->and($item['children'])->toHaveCount(2);
    });

    it('adds single child', function (): void {
        $item = Item::make('Settings')
            ->child(Item::make('Profile')->route('settings.profile'))
            ->child(Item::make('Security')->route('settings.security'))
            ->toArray();

        expect($item['children'])->toHaveCount(2);
    });

    it('sets visibility conditions', function (): void {
        $item = Item::make('Admin')
            ->route('admin')
            ->visible(fn () => true)
            ->toArray();

        expect($item)->toHaveKey('visible');
    });

    it('sets whenAuthenticated', function (): void {
        $item = Item::make('Dashboard')
            ->route('dashboard')
            ->whenAuthenticated()
            ->toArray();

        expect($item)->toHaveKey('visible');
    });

    it('sets whenGuest', function (): void {
        $item = Item::make('Login')
            ->route('login')
            ->whenGuest()
            ->toArray();

        expect($item)->toHaveKey('visible');
    });

    it('sets gate authorization', function (): void {
        $item = Item::make('Admin')
            ->route('admin')
            ->can('access-admin')
            ->toArray();

        expect($item)->toHaveKey('can', 'access-admin');
    });

    it('sets breadcrumbOnly', function (): void {
        $item = Item::make('User Details')
            ->route('users.show')
            ->breadcrumbOnly()
            ->toArray();

        expect($item)->toHaveKey('breadcrumbOnly', true);
    });

    it('sets navOnly', function (): void {
        $item = Item::make('Dashboard')
            ->route('dashboard')
            ->navOnly()
            ->toArray();

        expect($item)->toHaveKey('navOnly', true);
    });

    it('sets route params', function (): void {
        $item = Item::make('User')
            ->route('users.show')
            ->params(['user' => '*'])
            ->toArray();

        expect($item)->toHaveKey('params', ['user' => '*']);
    });

    it('sets custom meta', function (): void {
        $item = Item::make('Dashboard')
            ->route('dashboard')
            ->meta('badge', 5)
            ->toArray();

        expect($item)->toHaveKey('badge', 5);
    });

    it('sets badge shortcut', function (): void {
        $item = Item::make('Notifications')
            ->route('notifications')
            ->badge(10)
            ->toArray();

        expect($item)->toHaveKey('badge', 10);
    });

    it('sets badge with closure', function (): void {
        $item = Item::make('Notifications')
            ->route('notifications')
            ->badge(fn () => 5)
            ->toArray();

        expect($item)->toHaveKey('badge');
        expect($item['badge'])->toBeInstanceOf(Closure::class);
    });

    it('returns ItemBuilder instance for chaining', function (): void {
        $builder = Item::make('Dashboard');

        expect($builder)->toBeInstanceOf(ItemBuilder::class);
        expect($builder->route('dashboard'))->toBeInstanceOf(ItemBuilder::class);
        expect($builder->icon('home'))->toBeInstanceOf(ItemBuilder::class);
    });

    it('supports dynamic label with closure', function (): void {
        $item = Item::make(fn ($user) => "User: {$user->name}")
            ->route('users.show')
            ->toArray();

        expect($item['label'])->toBeInstanceOf(Closure::class);
    });
});
