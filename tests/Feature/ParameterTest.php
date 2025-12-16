<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

beforeEach(function () {
    // Mock routes
    Route::get('/admin/users', fn () => 'users')->name('admin.users.index');
    Route::get('/admin/users/create', fn () => 'create')->name('admin.users.create');
    Route::get('/admin/users/{user}/edit', fn ($user) => 'edit')->name('admin.users.edit');
    Route::get('/admin/users/{user}', fn ($user) => 'show')->name('admin.users.show');
    Route::get('/admin/dashboard', fn () => 'dashboard')->name('admin.dashboard');
});

test('wildcard params match any parameter value', function () {
    $config = [
        [
            'label' => 'Users',
            'route' => 'admin.users.index',
            'children' => [
                [
                    'label' => 'Edit User',
                    'route' => 'admin.users.edit',
                    'params' => ['user' => '*'],
                    'breadcrumbOnly' => true,
                ],
            ],
        ],
    ];

    $navigation = $this->createNavigation('main', $config);

    // Mock request with user ID 5
    request()->setRouteResolver(function () {
        $route = Route::getRoutes()->getByName('admin.users.edit');
        $route->bind(request());
        $route->setParameter('user', 5);

        return $route;
    });

    $breadcrumbs = $navigation->getBreadcrumbs('admin.users.edit', ['user' => 5]);

    expect($breadcrumbs)->toHaveCount(2)
        ->and($breadcrumbs[0]['label'])->toBe('Users')
        ->and($breadcrumbs[1]['label'])->toBe('Edit User')
        ->and($breadcrumbs[1]['route'])->toBe('admin.users.edit');
});

test('wildcard params work with different parameter values', function () {
    $config = [
        [
            'label' => 'Users',
            'route' => 'admin.users.index',
            'children' => [
                [
                    'label' => 'View User',
                    'route' => 'admin.users.show',
                    'params' => ['user' => '*'],
                    'breadcrumbOnly' => true,
                ],
            ],
        ],
    ];

    $navigation = $this->createNavigation('main', $config);

    // Test with user ID 123
    $breadcrumbs = $navigation->getBreadcrumbs('admin.users.show', ['user' => 123]);
    expect($breadcrumbs)->toHaveCount(2);

    // Test with user ID 999
    $breadcrumbs = $navigation->getBreadcrumbs('admin.users.show', ['user' => 999]);
    expect($breadcrumbs)->toHaveCount(2);
});

test('exact params require exact match', function () {
    $config = [
        [
            'label' => 'Users',
            'route' => 'admin.users.index',
            'children' => [
                [
                    'label' => 'Specific User',
                    'route' => 'admin.users.show',
                    'params' => ['user' => 5],
                    'breadcrumbOnly' => true,
                ],
            ],
        ],
    ];

    $navigation = $this->createNavigation('main', $config);

    // Should match user 5
    $breadcrumbs = $navigation->getBreadcrumbs('admin.users.show', ['user' => 5]);
    expect($breadcrumbs)->toHaveCount(2);

    // Should NOT match user 10
    $breadcrumbs = $navigation->getBreadcrumbs('admin.users.show', ['user' => 10]);
    expect($breadcrumbs)->toBeEmpty();
});

test('active state bubbles up with wildcard params', function () {
    $config = [
        [
            'label' => 'Admin',
            'route' => 'admin.dashboard',
            'children' => [
                [
                    'label' => 'Users',
                    'route' => 'admin.users.index',
                    'children' => [
                        [
                            'label' => 'Edit User',
                            'route' => 'admin.users.edit',
                            'params' => ['user' => '*'],
                            'breadcrumbOnly' => true,
                        ],
                    ],
                ],
            ],
        ],
    ];

    $navigation = $this->createNavigation('main', $config);

    // Mock request on edit page
    $mockRoute = Mockery::mock(Illuminate\Routing\Route::class);
    $mockRoute->shouldReceive('getName')->andReturn('admin.users.edit');
    $mockRoute->shouldReceive('parameters')->andReturn(['user' => 5]);

    request()->setRouteResolver(fn () => $mockRoute);

    $tree = $navigation->toTree(['user' => 5]);

    // Both Admin and Users should be active
    expect($tree[0]['isActive'])->toBeTrue()
        ->and($tree[0]['children'][0]['isActive'])->toBeTrue();
});

test('breadcrumb only items do not appear in navigation tree', function () {
    $config = [
        [
            'label' => 'Users',
            'route' => 'admin.users.index',
            'children' => [
                [
                    'label' => 'Edit User',
                    'route' => 'admin.users.edit',
                    'params' => ['user' => '*'],
                    'breadcrumbOnly' => true,
                ],
                [
                    'label' => 'Create User',
                    'route' => 'admin.users.create',
                ],
            ],
        ],
    ];

    $navigation = $this->createNavigation('main', $config);

    $tree = $navigation->toTree();

    // Should only have Create User, not Edit User
    expect($tree[0]['children'])->toHaveCount(1)
        ->and($tree[0]['children'][0]['label'])->toBe('Create User');
});

test('dynamic labels receive model instance', function () {
    $config = [
        [
            'label' => 'Users',
            'route' => 'admin.users.index',
            'children' => [
                [
                    'label' => fn ($user) => "Edit: {$user->name}",
                    'route' => 'admin.users.edit',
                    'params' => ['user' => '*'],
                    'breadcrumbOnly' => true,
                ],
            ],
        ],
    ];

    $navigation = $this->createNavigation('main', $config);

    // Mock a user model with getRouteKey for URL generation
    $user = new class()
    {
        public $name = 'John Doe';

        public $id = 5;

        public function __toString()
        {
            return (string) $this->id;
        }

        public function getRouteKey()
        {
            return $this->id;
        }
    };

    $breadcrumbs = $navigation->getBreadcrumbs('admin.users.edit', ['user' => $user]);

    expect($breadcrumbs)->toHaveCount(2)
        ->and($breadcrumbs[1]['label'])->toBe('Edit: John Doe');
});

test('dynamic labels work with multiple models', function () {
    Route::get('/admin/organizations/{organization}/users/{user}/edit', fn ($org, $user) => 'edit')
        ->name('admin.orgs.users.edit');

    $config = [
        [
            'label' => 'Users',
            'route' => 'admin.users.index',
            'children' => [
                [
                    'label' => fn ($params) => "Edit {$params['user']->name} in {$params['organization']->name}",
                    'route' => 'admin.orgs.users.edit',
                    'params' => ['organization' => '*', 'user' => '*'],
                    'breadcrumbOnly' => true,
                ],
            ],
        ],
    ];

    $navigation = $this->createNavigation('main', $config);

    $org = new class()
    {
        public $name = 'Acme Corp';

        public $id = 1;

        public function __toString()
        {
            return (string) $this->id;
        }

        public function getRouteKey()
        {
            return $this->id;
        }
    };

    $user = new class()
    {
        public $name = 'Jane Smith';

        public $id = 10;

        public function __toString()
        {
            return (string) $this->id;
        }

        public function getRouteKey()
        {
            return $this->id;
        }
    };

    $breadcrumbs = $navigation->getBreadcrumbs('admin.orgs.users.edit', [
        'organization' => $org,
        'user' => $user,
    ]);

    expect($breadcrumbs)->toHaveCount(2)
        ->and($breadcrumbs[1]['label'])->toBe('Edit Jane Smith in Acme Corp');
});

test('params can be mixed wildcard and exact', function () {
    Route::get('/admin/organizations/{organization}/users/{user}/edit', fn ($org, $user) => 'edit')
        ->name('admin.orgs.users.edit');

    $config = [
        [
            'label' => 'Users',
            'route' => 'admin.users.index',
            'children' => [
                [
                    'label' => 'Edit User in Org 5',
                    'route' => 'admin.orgs.users.edit',
                    'params' => ['organization' => 5, 'user' => '*'],
                    'breadcrumbOnly' => true,
                ],
            ],
        ],
    ];

    $navigation = $this->createNavigation('main', $config);

    // Should match org 5, any user
    $breadcrumbs = $navigation->getBreadcrumbs('admin.orgs.users.edit', [
        'organization' => 5,
        'user' => 99,
    ]);
    expect($breadcrumbs)->toHaveCount(2);

    // Should NOT match org 10
    $breadcrumbs = $navigation->getBreadcrumbs('admin.orgs.users.edit', [
        'organization' => 10,
        'user' => 99,
    ]);
    expect($breadcrumbs)->toBeEmpty();
});

test('routes without params still work normally', function () {
    $config = [
        [
            'label' => 'Users',
            'route' => 'admin.users.index',
            'children' => [
                [
                    'label' => 'Create User',
                    'route' => 'admin.users.create',
                    'breadcrumbOnly' => true,
                ],
            ],
        ],
    ];

    $navigation = $this->createNavigation('main', $config);

    $breadcrumbs = $navigation->getBreadcrumbs('admin.users.create');

    expect($breadcrumbs)->toHaveCount(2)
        ->and($breadcrumbs[0]['label'])->toBe('Users')
        ->and($breadcrumbs[1]['label'])->toBe('Create User');
});

// Tests for regular params (without wildcards)
test('navigation generates URLs with provided route params', function () {
    $config = [
        [
            'label' => 'Users',
            'route' => 'admin.users.index',
            'children' => [
                [
                    'label' => 'Edit User',
                    'route' => 'admin.users.edit',
                ],
            ],
        ],
    ];

    $navigation = $this->createNavigation('main', $config);

    $tree = $navigation->toTree(['user' => 5]);

    expect($tree[0]['children'][0]['url'])->toBe(route('admin.users.edit', ['user' => 5]));
});

test('breadcrumbs use provided route params for URLs', function () {
    $config = [
        [
            'label' => 'Users',
            'route' => 'admin.users.index',
        ],
    ];

    $navigation = $this->createNavigation('main', $config);

    $breadcrumbs = $navigation->getBreadcrumbs('admin.users.index', ['filter' => 'active']);

    expect($breadcrumbs[0]['url'])->toBe(route('admin.users.index', ['filter' => 'active']));
});

test('route params are passed through navigation hierarchy', function () {
    $config = [
        [
            'label' => 'Admin',
            'route' => 'admin.dashboard',
            'children' => [
                [
                    'label' => 'Users',
                    'route' => 'admin.users.index',
                    'children' => [
                        [
                            'label' => 'Edit User',
                            'route' => 'admin.users.edit',
                        ],
                    ],
                ],
            ],
        ],
    ];

    $navigation = $this->createNavigation('main', $config);

    $tree = $navigation->toTree(['user' => 42]);

    // Deep nested route should have the param
    expect($tree[0]['children'][0]['children'][0]['url'])
        ->toBe(route('admin.users.edit', ['user' => 42]));
});

test('route params work with external URLs', function () {
    $config = [
        [
            'label' => 'Documentation',
            'url' => 'https://docs.example.com',
        ],
        [
            'label' => 'Users',
            'route' => 'admin.users.index',
        ],
    ];

    $navigation = $this->createNavigation('main', $config);

    $tree = $navigation->toTree(['user' => 5]);

    // External URL should not be affected by params
    expect($tree[0]['url'])->toBe('https://docs.example.com')
        // But route URL should use params
        ->and($tree[1]['url'])->toBe(route('admin.users.index', ['user' => 5]));
});

test('empty params work correctly', function () {
    $config = [
        [
            'label' => 'Dashboard',
            'route' => 'admin.dashboard',
        ],
    ];

    $navigation = $this->createNavigation('main', $config);

    $tree = $navigation->toTree([]);

    expect($tree[0]['url'])->toBe(route('admin.dashboard'));
});

test('params are used in breadcrumb URL generation', function () {
    $config = [
        [
            'label' => 'Users',
            'route' => 'admin.users.index',
            'children' => [
                [
                    'label' => 'Edit User',
                    'route' => 'admin.users.edit',
                    'breadcrumbOnly' => true,
                ],
            ],
        ],
    ];

    $navigation = $this->createNavigation('main', $config);

    $breadcrumbs = $navigation->getBreadcrumbs('admin.users.edit', ['user' => 7]);

    expect($breadcrumbs[0]['url'])->toBe(route('admin.users.index', ['user' => 7]))
        ->and($breadcrumbs[1]['url'])->toBe(route('admin.users.edit', ['user' => 7]));
});

test('dynamic label closures are only evaluated on matching routes', function () {
    $callCount = 0;

    $config = [
        [
            'label' => 'Users',
            'route' => 'admin.users.index',
            'children' => [
                [
                    'label' => function ($user) use (&$callCount) {
                        $callCount++;

                        return "Edit: {$user->name}";
                    },
                    'route' => 'admin.users.edit',
                    'breadcrumbOnly' => true,
                    'params' => ['user' => '*'],
                ],
            ],
        ],
        [
            'label' => 'Dashboard',
            'route' => 'admin.dashboard',
        ],
    ];

    $navigation = $this->createNavigation('main', $config);

    // Get breadcrumbs for a different route - closure should NOT be called
    $breadcrumbs = $navigation->getBreadcrumbs('admin.dashboard', []);
    expect($callCount)->toBe(0);

    // Get breadcrumbs for the matching route - closure SHOULD be called once
    $user = new class()
    {
        public $name = 'John Doe';

        public $id = 5;

        public function __toString()
        {
            return (string) $this->id;
        }

        public function getRouteKey()
        {
            return $this->id;
        }
    };

    $breadcrumbs = $navigation->getBreadcrumbs('admin.users.edit', ['user' => $user]);
    expect($callCount)->toBe(1)
        ->and($breadcrumbs[1]['label'])->toBe('Edit: John Doe');
});

test('dynamic labels do not fail when evaluating on wrong routes', function () {
    $config = [
        [
            'label' => 'Users',
            'route' => 'admin.users.index',
            'children' => [
                [
                    'label' => fn ($user) => "Edit: {$user->name}",
                    'route' => 'admin.users.edit',
                    'breadcrumbOnly' => true,
                    'params' => ['user' => '*'],
                ],
            ],
        ],
        [
            'label' => 'Dashboard',
            'route' => 'admin.dashboard',
        ],
    ];

    $navigation = $this->createNavigation('main', $config);

    // This should not throw an error even though the closure expects a user object
    $breadcrumbs = $navigation->getBreadcrumbs('admin.dashboard', []);

    expect($breadcrumbs)->toHaveCount(1)
        ->and($breadcrumbs[0]['label'])->toBe('Dashboard');
});

test('multiple dynamic label closures only evaluate their own route', function () {
    $userCallCount = 0;
    $productCallCount = 0;

    $config = [
        [
            'label' => 'Users',
            'route' => 'admin.users.index',
            'children' => [
                [
                    'label' => function ($user) use (&$userCallCount) {
                        $userCallCount++;

                        return "Edit User: {$user->name}";
                    },
                    'route' => 'admin.users.edit',
                    'breadcrumbOnly' => true,
                    'params' => ['user' => '*'],
                ],
            ],
        ],
        [
            'label' => 'Products',
            'route' => 'admin.users.index', // Reusing route for test
            'children' => [
                [
                    'label' => function ($product) use (&$productCallCount) {
                        $productCallCount++;

                        return "Edit Product: {$product->name}";
                    },
                    'route' => 'admin.users.show', // Different route
                    'breadcrumbOnly' => true,
                    'params' => ['user' => '*'],
                ],
            ],
        ],
    ];

    $navigation = $this->createNavigation('main', $config);

    $user = new class()
    {
        public $name = 'Jane Doe';

        public $id = 10;

        public function __toString()
        {
            return (string) $this->id;
        }

        public function getRouteKey()
        {
            return $this->id;
        }
    };

    // Get breadcrumbs for users.edit - only user closure should be called
    $breadcrumbs = $navigation->getBreadcrumbs('admin.users.edit', ['user' => $user]);

    expect($userCallCount)->toBe(1)
        ->and($productCallCount)->toBe(0)
        ->and($breadcrumbs[1]['label'])->toBe('Edit User: Jane Doe');
});
