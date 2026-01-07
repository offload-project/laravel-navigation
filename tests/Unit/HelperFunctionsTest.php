<?php

declare(strict_types=1);

use OffloadProject\Navigation\ItemBuilder;

describe('Helper functions', function (): void {
    it('nav_item creates basic item', function (): void {
        $item = nav_item('Dashboard');

        expect($item)->toBeInstanceOf(ItemBuilder::class);
        expect($item->toArray())->toHaveKey('label', 'Dashboard');
    });

    it('nav_item with route and icon', function (): void {
        $item = nav_item('Dashboard', 'dashboard', 'home');

        expect($item->toArray())
            ->toHaveKey('label', 'Dashboard')
            ->toHaveKey('route', 'dashboard')
            ->toHaveKey('icon', 'home');
    });

    it('nav_item supports chaining', function (): void {
        $item = nav_item('Settings', 'settings')
            ->children([
                nav_item('Profile', 'settings.profile'),
            ]);

        expect($item->toArray()['children'])->toHaveCount(1);
    });

    it('nav_separator creates separator', function (): void {
        $item = nav_separator();

        expect($item)->toBeInstanceOf(ItemBuilder::class);
        expect($item->toArray())->toHaveKey('separator', true);
    });

    it('nav_divider creates divider with default spacing', function (): void {
        $item = nav_divider();

        expect($item)->toBeInstanceOf(ItemBuilder::class);
        expect($item->toArray())->toHaveKey('divider', 'default');
    });

    it('nav_divider creates divider with custom spacing', function (): void {
        $item = nav_divider('large');

        expect($item->toArray())->toHaveKey('divider', 'large');
    });

    it('nav_external creates external link', function (): void {
        $item = nav_external('Docs', 'https://docs.example.com', 'book');

        expect($item->toArray())
            ->toHaveKey('label', 'Docs')
            ->toHaveKey('url', 'https://docs.example.com')
            ->toHaveKey('icon', 'book');
    });

    it('nav_action creates action item', function (): void {
        $item = nav_action('Logout', 'logout', 'post', 'log-out');

        expect($item->toArray())
            ->toHaveKey('label', 'Logout')
            ->toHaveKey('route', 'logout')
            ->toHaveKey('method', 'post')
            ->toHaveKey('icon', 'log-out');
    });

    it('nav_action defaults to post method', function (): void {
        $item = nav_action('Logout', 'logout');

        expect($item->toArray())->toHaveKey('method', 'post');
    });

    it('helper functions work together for full navigation', function (): void {
        $navigation = [
            nav_item('Dashboard', 'dashboard', 'home'),
            nav_separator(),
            nav_item('Users', 'users.index', 'users')
                ->can('view-users')
                ->children([
                    nav_item('All Users', 'users.index'),
                    nav_item('Create User', 'users.create'),
                ]),
            nav_divider('large'),
            nav_external('Documentation', 'https://docs.example.com', 'book'),
            nav_action('Logout', 'logout', 'post', 'log-out'),
        ];

        expect($navigation)->toHaveCount(6);
        expect($navigation[0]->toArray()['label'])->toBe('Dashboard');
        expect($navigation[1]->toArray())->toHaveKey('separator');
        expect($navigation[2]->toArray()['children'])->toHaveCount(2);
        expect($navigation[3]->toArray())->toHaveKey('divider');
        expect($navigation[4]->toArray())->toHaveKey('url');
        expect($navigation[5]->toArray())->toHaveKey('method');
    });
});
