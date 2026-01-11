<?php

declare(strict_types=1);

use OffloadProject\Navigation\Data\NavigationItem;

describe('Shorthand config syntax', function (): void {
    it('converts shorthand to full format with label and route', function (): void {
        $item = NavigationItem::fromArray(['Dashboard', 'dashboard']);

        expect($item->label)->toBe('Dashboard');
        expect($item->route)->toBe('dashboard');
    });

    it('converts shorthand with icon', function (): void {
        $item = NavigationItem::fromArray(['Dashboard', 'dashboard', 'home']);

        expect($item->label)->toBe('Dashboard');
        expect($item->route)->toBe('dashboard');
        expect($item->icon)->toBe('home');
    });

    it('detects URLs in shorthand', function (): void {
        $item = NavigationItem::fromArray(['Docs', 'https://docs.example.com', 'book']);

        expect($item->label)->toBe('Docs');
        expect($item->url)->toBe('https://docs.example.com');
        expect($item->route)->toBeNull();
        expect($item->icon)->toBe('book');
    });

    it('detects http URLs in shorthand', function (): void {
        $item = NavigationItem::fromArray(['Docs', 'http://docs.example.com']);

        expect($item->url)->toBe('http://docs.example.com');
        expect($item->route)->toBeNull();
    });

    it('converts shorthand with children', function (): void {
        $item = NavigationItem::fromArray([
            'Settings',
            'settings',
            'cog',
            [
                ['Profile', 'settings.profile'],
                ['Security', 'settings.security'],
            ],
        ]);

        expect($item->label)->toBe('Settings');
        expect($item->route)->toBe('settings');
        expect($item->icon)->toBe('cog');
        expect($item->children)->toHaveCount(2);
        expect($item->children[0]->label)->toBe('Profile');
        expect($item->children[1]->label)->toBe('Security');
    });

    it('still supports full array format', function (): void {
        $item = NavigationItem::fromArray([
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'icon' => 'home',
        ]);

        expect($item->label)->toBe('Dashboard');
        expect($item->route)->toBe('dashboard');
        expect($item->icon)->toBe('home');
    });

    it('does not treat full format as shorthand', function (): void {
        $item = NavigationItem::fromArray([
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'icon' => 'home',
            'can' => 'access-dashboard',
        ]);

        expect($item->label)->toBe('Dashboard');
        expect($item->can)->toBe('access-dashboard');
    });

    it('handles mixed shorthand and full format in children', function (): void {
        $item = NavigationItem::fromArray([
            'label' => 'Settings',
            'route' => 'settings',
            'children' => [
                ['Profile', 'settings.profile'],
                ['label' => 'Security', 'route' => 'settings.security', 'icon' => 'shield'],
            ],
        ]);

        expect($item->children)->toHaveCount(2);
        expect($item->children[0]->label)->toBe('Profile');
        expect($item->children[1]->icon)->toBe('shield');
    });

    it('supports label only shorthand', function (): void {
        // A simple label-only item is unlikely but valid
        $item = NavigationItem::fromArray([
            'label' => 'Section Header',
        ]);

        expect($item->label)->toBe('Section Header');
        expect($item->route)->toBeNull();
    });

    it('preserves closure labels in shorthand', function (): void {
        $closure = fn () => 'Dynamic Label';
        $item = NavigationItem::fromArray([$closure, 'dashboard']);

        expect($item->label)->toBe($closure);
        expect($item->hasDynamicLabel())->toBeTrue();
    });
});
