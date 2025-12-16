<?php

declare(strict_types=1);

it('shows items when visible is true', function () {
    $items = [
        ['label' => 'Visible Item', 'route' => 'dashboard', 'visible' => true],
        ['label' => 'Hidden Item', 'route' => 'users.index', 'visible' => false],
    ];

    $navigation = $this->createNavigation('test', $items);
    $tree = $navigation->toTree();

    expect($tree)->toHaveCount(1)
        ->and($tree[0]['label'])->toBe('Visible Item');
});

it('evaluates callable for visible', function () {
    $items = [
        ['label' => 'Admin Only', 'route' => 'dashboard', 'visible' => fn () => auth()->check()],
    ];

    // Not authenticated
    $navigation = $this->createNavigation('test', $items);
    $tree = $navigation->toTree();
    expect($tree)->toHaveCount(0);

    // Mock authentication
    $this->actingAs($this->createMockUser());

    $tree = $navigation->toTree();
    expect($tree)->toHaveCount(1);
});

it('checks gates with can attribute', function () {
    $items = [
        ['label' => 'Admin Panel', 'route' => 'admin.index', 'can' => 'access-admin'],
    ];

    $this->actingAs($this->createMockUser(['access-admin']));

    $navigation = $this->createNavigation('test', $items);
    $tree = $navigation->toTree();

    expect($tree)->toHaveCount(1);
});

it('hides items when user lacks permission', function () {
    $items = [
        ['label' => 'Admin Panel', 'route' => 'admin.index', 'can' => 'access-admin'],
    ];

    $this->actingAs($this->createMockUser([])); // No permissions

    $navigation = $this->createNavigation('test', $items);
    $tree = $navigation->toTree();

    expect($tree)->toHaveCount(0);
});

it('handles can with model arguments', function () {
    $items = [
        ['label' => 'Edit User', 'route' => 'users.edit', 'can' => ['update', new stdClass()]],
    ];

    $this->actingAs($this->createMockUser(['update']));

    $navigation = $this->createNavigation('test', $items);
    $tree = $navigation->toTree();

    expect($tree)->toHaveCount(1);
});

it('filters children based on visibility', function () {
    $items = [
        [
            'label' => 'Parent',
            'route' => 'dashboard',
            'children' => [
                ['label' => 'Visible Child', 'route' => 'child.1', 'visible' => true],
                ['label' => 'Hidden Child', 'route' => 'child.2', 'visible' => false],
            ],
        ],
    ];

    $navigation = $this->createNavigation('test', $items);
    $tree = $navigation->toTree();

    expect($tree[0]['children'])->toHaveCount(1)
        ->and($tree[0]['children'][0]['label'])->toBe('Visible Child');
});

it('combines visible and can attributes', function () {
    $items = [
        [
            'label' => 'Admin Only When Enabled',
            'route' => 'admin.index',
            'visible' => true,
            'can' => 'access-admin',
        ],
    ];

    $this->actingAs($this->createMockUser(['access-admin']));

    $navigation = $this->createNavigation('test', $items);
    $tree = $navigation->toTree();

    expect($tree)->toHaveCount(1);
});

it('hides items when not authenticated and can is set', function () {
    $items = [
        ['label' => 'Admin Panel', 'route' => 'admin.index', 'can' => 'access-admin'],
    ];

    // No authentication
    $navigation = $this->createNavigation('test', $items);
    $tree = $navigation->toTree();

    expect($tree)->toHaveCount(0);
});

it('does not include visible and can in output', function () {
    $items = [
        [
            'label' => 'Item',
            'route' => 'dashboard',
            'visible' => true,
            'can' => 'some-ability',
            'customAttr' => 'value',
        ],
    ];

    $this->actingAs($this->createMockUser(['some-ability']));

    $navigation = $this->createNavigation('test', $items);
    $tree = $navigation->toTree();

    expect($tree[0])->not->toHaveKey('visible')
        ->and($tree[0])->not->toHaveKey('can')
        ->and($tree[0])->toHaveKey('customAttr')
        ->and($tree[0]['customAttr'])->toBe('value');
});
