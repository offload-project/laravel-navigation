<?php

declare(strict_types=1);

beforeEach(function () {
    $this->config = $this->getTestConfig();
});

it('generates a tree structure', function () {
    $navigation = $this->createNavigation('main', $this->config['navigations']['main']);
    $tree = $navigation->toTree();

    expect($tree)->toBeArray()
        ->and($tree)->toHaveCount(3)
        ->and($tree[0])->toHaveKeys(['id', 'label', 'url', 'isActive', 'children', 'icon']);
});

it('includes node ids in tree', function () {
    $navigation = $this->createNavigation('main', $this->config['navigations']['main']);
    $tree = $navigation->toTree();

    expect($tree[0]['id'])->toBe('nav-main-0')
        ->and($tree[1]['id'])->toBe('nav-main-1')
        ->and($tree[1]['children'][0]['id'])->toBe('nav-main-1-0');
});

it('resolves route names to URLs', function () {
    $navigation = $this->createNavigation('main', $this->config['navigations']['main']);
    $tree = $navigation->toTree();

    expect($tree[0]['url'])->toBe(url('/'))
        ->and($tree[1]['url'])->toBe(url('/users'));
});

it('handles external URLs', function () {
    $navigation = $this->createNavigation('footer', $this->config['navigations']['footer']);
    $tree = $navigation->toTree();

    expect($tree[0]['url'])->toBe('https://docs.example.com');
});

it('includes method for action items', function () {
    $navigation = $this->createNavigation('user_menu', $this->config['navigations']['user_menu']);
    $tree = $navigation->toTree();

    expect($tree[2])->toHaveKey('method')
        ->and($tree[2]['method'])->toBe('post');
});

it('does not include method for regular links', function () {
    $navigation = $this->createNavigation('main', $this->config['navigations']['main']);
    $tree = $navigation->toTree();

    expect($tree[0])->not->toHaveKey('method');
});

it('processes nested children', function () {
    $navigation = $this->createNavigation('main', $this->config['navigations']['main']);
    $tree = $navigation->toTree();

    expect($tree[1]['children'])->toHaveCount(3)
        ->and($tree[1]['children'][0]['label'])->toBe('All Users')
        ->and($tree[1]['children'][1]['label'])->toBe('Roles');
});

it('resolves route parameters', function () {
    $items = [
        ['label' => 'User Profile', 'route' => 'users.show'],
    ];

    $navigation = $this->createNavigation('test', $items);
    $tree = $navigation->toTree(['user' => 123]);

    expect($tree[0]['url'])->toBe(url('/users/123'));
});

it('includes custom attributes', function () {
    $items = [
        ['label' => 'Dashboard', 'route' => 'dashboard', 'custom' => 'value', 'badge' => '5'],
    ];

    $navigation = $this->createNavigation('test', $items);
    $tree = $navigation->toTree();

    expect($tree[0])->toHaveKey('custom')
        ->and($tree[0]['custom'])->toBe('value')
        ->and($tree[0])->toHaveKey('badge')
        ->and($tree[0]['badge'])->toBe('5');
});

it('generates proper output structure for navigation items', function () {
    $items = [
        ['label' => 'Dashboard', 'route' => 'dashboard'],
    ];

    $navigation = $this->createNavigation('test', $items);
    $tree = $navigation->toTree();

    expect($tree[0])->toHaveKey('url')
        ->and($tree[0])->toHaveKey('isActive')
        ->and($tree[0])->toHaveKey('id')
        ->and($tree[0])->toHaveKey('label')
        ->and($tree[0])->toHaveKey('children');
});

it('excludes breadcrumbOnly items from navigation', function () {
    $items = [
        ['label' => 'Dashboard', 'route' => 'dashboard'],
        ['label' => 'Edit User', 'route' => 'users.edit', 'breadcrumbOnly' => true],
        ['label' => 'Settings', 'route' => 'settings.index'],
    ];

    $navigation = $this->createNavigation('test', $items);
    $tree = $navigation->toTree();

    expect($tree)->toHaveCount(2)
        ->and($tree[0]['label'])->toBe('Dashboard')
        ->and($tree[1]['label'])->toBe('Settings');
});

it('includes breadcrumbOnly items in breadcrumbs', function () {
    $items = [
        [
            'label' => 'Users',
            'route' => 'users.index',
            'children' => [
                ['label' => 'Edit User', 'route' => 'users.edit', 'breadcrumbOnly' => true],
            ],
        ],
    ];

    $navigation = $this->createNavigation('test', $items);
    $breadcrumbs = $navigation->getBreadcrumbs('users.edit');

    expect($breadcrumbs)->toHaveCount(2)
        ->and($breadcrumbs[0]['label'])->toBe('Users')
        ->and($breadcrumbs[1]['label'])->toBe('Edit User');
});

it('excludes navOnly items from breadcrumbs', function () {
    $items = [
        [
            'label' => 'Admin',
            'route' => 'dashboard',
            'navOnly' => true,
            'children' => [
                ['label' => 'Users', 'route' => 'users.index'],
            ],
        ],
    ];

    $navigation = $this->createNavigation('test', $items);
    $breadcrumbs = $navigation->getBreadcrumbs('users.index');

    // Should only have "Users", not "Admin"
    expect($breadcrumbs)->toHaveCount(1)
        ->and($breadcrumbs[0]['label'])->toBe('Users');
});

it('includes navOnly items in navigation', function () {
    $items = [
        ['label' => 'Dashboard', 'route' => 'dashboard'],
        ['label' => 'Admin Section', 'route' => 'dashboard', 'navOnly' => true],
    ];

    $navigation = $this->createNavigation('test', $items);
    $tree = $navigation->toTree();

    expect($tree)->toHaveCount(2)
        ->and($tree[1]['label'])->toBe('Admin Section');
});

it('does not include navOnly and breadcrumbOnly in output', function () {
    $items = [
        [
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'navOnly' => true,
            'customAttr' => 'value',
        ],
    ];

    $navigation = $this->createNavigation('test', $items);
    $tree = $navigation->toTree();

    expect($tree[0])->not->toHaveKey('navOnly')
        ->and($tree[0])->not->toHaveKey('breadcrumbOnly')
        ->and($tree[0])->toHaveKey('customAttr')
        ->and($tree[0]['customAttr'])->toBe('value');
});

it('includes meta on navigation items', function () {
    $items = [
        [
            'label' => 'Dashboard',
            'route' => 'dashboard',
            'meta' => [
                'badge' => 5,
                'badgeColor' => 'red',
                'customData' => ['foo' => 'bar'],
            ],
        ],
    ];

    $navigation = $this->createNavigation('test', $items);
    $tree = $navigation->toTree();

    expect($tree[0])->toHaveKey('meta')
        ->and($tree[0]['meta'])->toBe([
            'badge' => 5,
            'badgeColor' => 'red',
            'customData' => ['foo' => 'bar'],
        ]);
});

it('does not include meta when not provided', function () {
    $items = [
        ['label' => 'Dashboard', 'route' => 'dashboard'],
        ['label' => 'Users', 'route' => 'users.index'],
    ];

    $navigation = $this->createNavigation('test', $items);
    $tree = $navigation->toTree();

    expect($tree[0])->not->toHaveKey('meta')
        ->and($tree[1])->not->toHaveKey('meta');
});

it('handles nested children with meta', function () {
    $items = [
        [
            'label' => 'Admin',
            'route' => 'dashboard',
            'meta' => ['section' => true],
            'children' => [
                [
                    'label' => 'Users',
                    'route' => 'users.index',
                    'meta' => ['icon' => 'users'],
                ],
            ],
        ],
    ];

    $navigation = $this->createNavigation('test', $items);
    $tree = $navigation->toTree();

    expect($tree[0]['meta'])->toBe(['section' => true])
        ->and($tree[0]['children'][0]['meta'])->toBe(['icon' => 'users']);
});

it('handles items with only meta (no label)', function () {
    $items = [
        ['label' => 'Home', 'route' => 'dashboard'],
        ['meta' => ['type' => 'separator']],
        ['label' => 'About', 'route' => 'about'],
        ['meta' => ['type' => 'divider', 'spacing' => 'large']],
        ['label' => 'Contact', 'route' => 'contact'],
    ];

    $navigation = $this->createNavigation('test', $items);
    $tree = $navigation->toTree();

    expect($tree)->toHaveCount(5)
        ->and($tree[0]['label'])->toBe('Home')
        ->and($tree[1])->toHaveKey('id')
        ->and($tree[1])->toHaveKey('meta')
        ->and($tree[1]['meta']['type'])->toBe('separator')
        ->and($tree[1])->not->toHaveKey('label')
        ->and($tree[1])->not->toHaveKey('isActive')
        ->and($tree[2]['label'])->toBe('About')
        ->and($tree[3]['meta'])->toBe(['type' => 'divider', 'spacing' => 'large'])
        ->and($tree[4]['label'])->toBe('Contact');
});

it('handles meta-only items in children', function () {
    $items = [
        [
            'label' => 'Dropdown',
            'route' => 'dropdown',
            'children' => [
                ['label' => 'Child 1', 'route' => 'child1'],
                ['meta' => ['type' => 'separator']],
                ['label' => 'Child 2', 'route' => 'child2'],
            ],
        ],
    ];

    $navigation = $this->createNavigation('test', $items);
    $tree = $navigation->toTree();

    expect($tree[0]['children'])->toHaveCount(3)
        ->and($tree[0]['children'][0]['label'])->toBe('Child 1')
        ->and($tree[0]['children'][1]['meta']['type'])->toBe('separator')
        ->and($tree[0]['children'][1])->not->toHaveKey('label')
        ->and($tree[0]['children'][2]['label'])->toBe('Child 2');
});
