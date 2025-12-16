<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use OffloadProject\Navigation\Adapters\WayfinderAdapter;

beforeEach(function () {
    // Setup mock routes with Wayfinder navigation metadata
    Route::get('/dashboard', fn () => 'dashboard')
        ->name('dashboard')
        ->defaults('navigation', ['label' => 'Dashboard', 'order' => 1]);

    Route::get('/users', fn () => 'users')
        ->name('users.index')
        ->defaults('navigation', ['label' => 'Users', 'order' => 2, 'parent' => 'dashboard']);

    Route::get('/users/create', fn () => 'create')
        ->name('users.create')
        ->defaults('navigation', ['label' => 'Create User', 'parent' => 'users.index']);

    Route::get('/settings', fn () => 'settings')
        ->name('settings.index')
        ->defaults('navigation', ['label' => 'Settings', 'order' => 3]);

    Route::post('/logout', fn () => 'logout')
        ->name('logout')
        ->defaults('navigation', ['label' => 'Logout', 'order' => 4]);
});

it('converts wayfinder routes to navigation array', function () {
    $adapter = WayfinderAdapter::fromWayfinder();
    $nav = $adapter->toArray();

    expect($nav)->toBeArray()
        ->and($nav)->not->toBeEmpty();
});

it('adds icons from icon map', function () {
    $adapter = WayfinderAdapter::fromWayfinder()
        ->withIcons([
            'dashboard' => 'home',
            'users.index' => 'users',
        ]);

    $nav = $adapter->toArray();

    expect($nav[0]['icon'])->toBe('home');
});

it('adds methods from method map', function () {
    $adapter = WayfinderAdapter::fromWayfinder()
        ->withMethods([
            'logout' => 'post',
        ]);

    $nav = $adapter->toArray();

    $logoutItem = collect($nav)->firstWhere('route', 'logout');
    expect($logoutItem['method'])->toBe('post');
});

it('adds custom attributes', function () {
    $adapter = WayfinderAdapter::fromWayfinder()
        ->withAttributes([
            'users.index' => ['badge' => '5', 'color' => 'red'],
        ]);

    $nav = $adapter->toArray();

    $usersItem = collect($nav)->firstWhere('route', 'dashboard')['children'][0] ?? null;

    expect($usersItem)->not->toBeNull()
        ->and($usersItem['badge'])->toBe('5')
        ->and($usersItem['color'])->toBe('red');
});

it('excludes specified routes', function () {
    $adapter = WayfinderAdapter::fromWayfinder()
        ->exclude(['settings.index']);

    $nav = $adapter->toArray();

    $settingsItem = collect($nav)->firstWhere('route', 'settings.index');
    expect($settingsItem)->toBeNull();
});

it('builds hierarchical structure from parent relationships', function () {
    $adapter = WayfinderAdapter::fromWayfinder();
    $nav = $adapter->toArray();

    $dashboard = collect($nav)->firstWhere('route', 'dashboard');

    expect($dashboard)->toHaveKey('children')
        ->and($dashboard['children'])->toHaveCount(1)
        ->and($dashboard['children'][0]['route'])->toBe('users.index');
});

it('handles nested children', function () {
    $adapter = WayfinderAdapter::fromWayfinder();
    $nav = $adapter->toArray();

    $dashboard = collect($nav)->firstWhere('route', 'dashboard');
    $users = $dashboard['children'][0];

    expect($users)->toHaveKey('children')
        ->and($users['children'])->toHaveCount(1)
        ->and($users['children'][0]['route'])->toBe('users.create');
});

it('generates labels from route names when not provided', function () {
    Route::get('/my-awesome-page', fn () => 'page')
        ->name('my.awesome.page.index')
        ->defaults('navigation', ['order' => 1]); // Provide at least some metadata

    $adapter = WayfinderAdapter::fromWayfinder();
    $nav = $adapter->toArray();

    $item = collect($nav)->firstWhere('route', 'my.awesome.page.index');

    // Add a check to see if item exists
    expect($item)->not->toBeNull()
        ->and($item['label'])->toBe('My Awesome Page');
});

it('can merge with existing config', function () {
    $existingConfig = [
        ['label' => 'Custom Item', 'route' => 'custom.route', 'icon' => 'star'],
    ];

    $adapter = WayfinderAdapter::fromWayfinder();
    $merged = $adapter->mergeWith($existingConfig);

    expect($merged)->toContain(['label' => 'Custom Item', 'route' => 'custom.route', 'icon' => 'star']);
});
