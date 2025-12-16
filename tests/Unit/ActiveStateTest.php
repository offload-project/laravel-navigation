<?php

declare(strict_types=1);

beforeEach(function () {
    $this->config = $this->getTestConfig();
});

it('marks exact route as active', function () {
    // Simulate being on the users.index route
    $this->get('/users');

    $navigation = $this->createNavigation('main', $this->config['navigations']['main']);
    $tree = $navigation->toTree();

    expect($tree[1]['isActive'])->toBeTrue()
        ->and($tree[0]['isActive'])->toBeFalse();
});

it('marks parent as active when on child route', function () {
    // Simulate being on a child route and keep the request active
    $this->get('/users/roles');

    // Verify the route was set
    expect(request()->route()->getName())->toBe('users.roles.index');

    $navigation = $this->createNavigation('main', $this->getTestConfig()['navigations']['main']);
    $tree = $navigation->toTree();

    // Parent should be active
    expect($tree[1]['isActive'])->toBeTrue();
});

it('marks child as active when on that route', function () {
    $this->get('/users/roles');

    // Verify the route was set
    expect(request()->route()->getName())->toBe('users.roles.index');

    $navigation = $this->createNavigation('main', $this->getTestConfig()['navigations']['main']);
    $tree = $navigation->toTree();

    // Child should be active
    expect($tree[1]['children'][1]['isActive'])->toBeTrue()
        ->and($tree[1]['children'][0]['isActive'])->toBeFalse();
});

it('handles nested active states', function () {
    $items = [
        [
            'label' => 'Settings',
            'route' => 'settings.index',
            'children' => [
                ['label' => 'General', 'route' => 'settings.general'],
                ['label' => 'Billing', 'route' => 'settings.billing'],
            ],
        ],
    ];

    $this->get('/settings/billing');

    $navigation = $this->createNavigation('test', $items);
    $tree = $navigation->toTree();

    expect($tree[0]['isActive'])->toBeTrue()
        ->and($tree[0]['children'][1]['isActive'])->toBeTrue()
        ->and($tree[0]['children'][0]['isActive'])->toBeFalse();
});
