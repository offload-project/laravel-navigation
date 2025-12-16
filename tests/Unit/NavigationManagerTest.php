<?php

declare(strict_types=1);

use OffloadProject\Navigation\Navigation;

beforeEach(function () {
    $this->config = $this->getTestConfig();
    $this->manager = $this->createNavigationManager($this->config);
});

it('can get a navigation by name', function () {
    $navigation = $this->manager->get('main');

    expect($navigation)->toBeInstanceOf(Navigation::class);
});

it('returns empty array for non-existent navigation', function () {
    $navigation = $this->manager->get('non-existent');
    $tree = $navigation->toTree();

    expect($tree)->toBe([]);
});

it('can get all navigation names', function () {
    $names = $this->manager->getAllNavigations();

    expect($names)->toBe(['main', 'user_menu', 'footer']);
});

it('can generate breadcrumbs from route name', function () {
    $breadcrumbs = $this->manager->breadcrumbs('main', 'users.roles.index');

    expect($breadcrumbs)->toHaveCount(2)
        ->and($breadcrumbs[0]['label'])->toBe('Users')
        ->and($breadcrumbs[1]['label'])->toBe('Roles');
});

// Update the existing test to be more explicit
it('can generate breadcrumbs from specific navigation and route name', function () {
    $breadcrumbs = $this->manager->breadcrumbs('main', 'users.roles.index');

    expect($breadcrumbs)->toHaveCount(2)
        ->and($breadcrumbs[0]['label'])->toBe('Users')
        ->and($breadcrumbs[1]['label'])->toBe('Roles');
});

// Add new tests for auto-discovery
it('can find breadcrumbs across all navigations when nav not specified', function () {
    $breadcrumbs = $this->manager->breadcrumbs(routeName: 'users.roles.index');

    expect($breadcrumbs)->toHaveCount(2)
        ->and($breadcrumbs[0]['label'])->toBe('Users')
        ->and($breadcrumbs[1]['label'])->toBe('Roles');
});

it('searches multiple navigations until route is found', function () {
    $breadcrumbs = $this->manager->breadcrumbs(routeName: 'profile.edit');

    expect($breadcrumbs)->toHaveCount(1)
        ->and($breadcrumbs[0]['label'])->toBe('Profile')
        ->and($breadcrumbs[0]['route'])->toBe('profile.edit');
});

it('returns empty array when route not found in any navigation', function () {
    $breadcrumbs = $this->manager->breadcrumbs(routeName: 'non.existent.route');

    expect($breadcrumbs)->toBe([]);
});

it('uses current route when no route name provided', function () {
    $this->get('/users/roles');

    $breadcrumbs = $this->manager->breadcrumbs();

    expect($breadcrumbs)->toHaveCount(2)
        ->and($breadcrumbs[0]['label'])->toBe('Users');
});

it('can use current route with specific navigation', function () {
    $this->get('/profile');

    $breadcrumbs = $this->manager->breadcrumbs('user_menu');

    expect($breadcrumbs)->toHaveCount(1)
        ->and($breadcrumbs[0]['label'])->toBe('Profile');
});

it('returns breadcrumbs from first matching navigation', function () {
    // Add same route to multiple navigations
    $config = $this->config;
    $config['navigations']['secondary'] = [
        ['label' => 'Secondary Dashboard', 'route' => 'dashboard'],
    ];

    $manager = $this->createNavigationManager($config);
    $breadcrumbs = $manager->breadcrumbs(routeName: 'dashboard');

    // Should return from 'main' navigation (first in config)
    expect($breadcrumbs)->toHaveCount(1)
        ->and($breadcrumbs[0]['label'])->toBe('Dashboard');
});
