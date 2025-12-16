<?php

declare(strict_types=1);

namespace OffloadProject\Navigation;

use Illuminate\Support\ServiceProvider;
use OffloadProject\Navigation\Commands\CompileIconsCommand;
use OffloadProject\Navigation\Commands\ValidateNavigationCommand;
use OffloadProject\Navigation\Contracts\IconCompilerInterface;
use OffloadProject\Navigation\Support\ItemVisibilityResolver;

final class NavigationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/navigation.php',
            'navigation'
        );

        $this->app->singleton(IconCompilerInterface::class, IconCompiler::class);
        $this->app->singleton(ItemVisibilityResolver::class);

        $this->app->singleton(NavigationManager::class, function ($app) {
            return new NavigationManager(
                $app['config']['navigation'],
                $app->make(IconCompilerInterface::class),
                $app->make(ItemVisibilityResolver::class)
            );
        });

        $this->app->alias(NavigationManager::class, 'navigation');
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/navigation.php' => config_path('navigation.php'),
            ], 'navigation-config');

            $this->commands([
                CompileIconsCommand::class,
                ValidateNavigationCommand::class,
            ]);
        }
    }
}
