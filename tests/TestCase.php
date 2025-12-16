<?php

declare(strict_types=1);

namespace OffloadProject\Navigation\Tests;

use Illuminate\Contracts\Auth\Authenticatable;
use OffloadProject\Navigation\Contracts\IconCompilerInterface;
use OffloadProject\Navigation\IconCompiler;
use OffloadProject\Navigation\Navigation;
use OffloadProject\Navigation\NavigationManager;
use OffloadProject\Navigation\NavigationServiceProvider;
use OffloadProject\Navigation\Support\ItemVisibilityResolver;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            NavigationServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Setup test routes
        $app['router']->get('/', fn() => 'home')->name('dashboard');
        $app['router']->get('/users', fn() => 'users')->name('users.index');

        // Put specific /users/* routes BEFORE the parameterized route
        $app['router']->get('/users/roles', fn() => 'roles')->name('users.roles.index');
        $app['router']->get('/users/permissions', fn() => 'permissions')->name('users.permissions.index');

        // Now the parameterized route
        $app['router']->get('/users/{user}', fn($user) => "user {$user}")->name('users.show');

        $app['router']->get('/settings', fn() => 'settings')->name('settings.index');
        $app['router']->get('/settings/general', fn() => 'general')->name('settings.general');
        $app['router']->get('/settings/billing', fn() => 'billing')->name('settings.billing');
        $app['router']->get('/profile', fn() => 'profile')->name('profile.edit');
        $app['router']->post('/logout', fn() => 'logout')->name('logout');
        $app['router']->get('/privacy', fn() => 'privacy')->name('legal.privacy');
        $app['router']->get('/terms', fn() => 'terms')->name('legal.terms');
    }

    protected function getTestConfig(): array
    {
        return [
            'navigations' => [
                'main' => [
                    [
                        'label' => 'Dashboard',
                        'route' => 'dashboard',
                        'icon' => 'home',
                    ],
                    [
                        'label' => 'Users',
                        'route' => 'users.index',
                        'icon' => 'users',
                        'children' => [
                            ['label' => 'All Users', 'route' => 'users.index'],
                            ['label' => 'Roles', 'route' => 'users.roles.index'],
                            ['label' => 'Permissions', 'route' => 'users.permissions.index'],
                        ],
                    ],
                    [
                        'label' => 'Settings',
                        'route' => 'settings.index',
                        'icon' => 'settings',
                    ],
                ],
                'user_menu' => [
                    ['label' => 'Profile', 'route' => 'profile.edit', 'icon' => 'user'],
                    ['label' => 'Settings', 'route' => 'settings.index', 'icon' => 'settings'],
                    ['label' => 'Logout', 'route' => 'logout', 'method' => 'post', 'icon' => 'log-out'],
                ],
                'footer' => [
                    ['label' => 'Documentation', 'url' => 'https://docs.example.com'],
                    ['label' => 'Privacy Policy', 'route' => 'legal.privacy'],
                    ['label' => 'Terms of Service', 'route' => 'legal.terms'],
                ],
            ],
            'icons' => [
                'compiled_path' => storage_path('navigation/icons.php'),
            ],
        ];
    }

    protected function createMockUser(array $permissions = []): Authenticatable
    {
        return new class($permissions) implements Authenticatable {
            private array $permissions;

            public function __construct(array $permissions = [])
            {
                $this->permissions = $permissions;
            }

            public function can($ability, $arguments = [])
            {
                return in_array($ability, $this->permissions);
            }

            public function getAuthIdentifierName()
            {
                return 'id';
            }

            public function getAuthIdentifier()
            {
                return 1;
            }

            public function getAuthPassword()
            {
                return 'password';
            }

            public function getRememberToken()
            {
                return null;
            }

            public function setRememberToken($value)
            {
            }

            public function getRememberTokenName()
            {
                return null;
            }

            public function getAuthPasswordName()
            {
                return 'password';
            }
        };
    }

    /**
     * Create a Navigation instance with default dependencies.
     *
     * @param array<int, array<string, mixed>> $items
     */
    protected function createNavigation(
        string                  $name,
        array                   $items,
        ?IconCompilerInterface  $iconCompiler = null,
        ?ItemVisibilityResolver $visibilityResolver = null
    ): Navigation
    {
        return new Navigation(
            $name,
            $items,
            $iconCompiler ?? new IconCompiler(),
            $visibilityResolver ?? new ItemVisibilityResolver()
        );
    }

    /**
     * Create a NavigationManager instance with default dependencies.
     *
     * @param array<string, mixed> $config
     */
    protected function createNavigationManager(
        array                   $config,
        ?IconCompilerInterface  $iconCompiler = null,
        ?ItemVisibilityResolver $visibilityResolver = null
    ): NavigationManager
    {
        return new NavigationManager(
            $config,
            $iconCompiler ?? new IconCompiler(),
            $visibilityResolver ?? new ItemVisibilityResolver()
        );
    }
}
