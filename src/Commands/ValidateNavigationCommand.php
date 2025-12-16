<?php

declare(strict_types=1);

namespace OffloadProject\Navigation\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Route;

final class ValidateNavigationCommand extends Command
{
    protected $signature = 'navigation:validate';

    protected $description = 'Validate that all routes referenced in navigation config exist';

    public function handle(): int
    {
        $config = config('navigation.navigations', []);
        $errors = [];

        foreach ($config as $navName => $items) {
            $this->info("Validating navigation: {$navName}");
            $this->validateItems($items, $navName, $errors);
        }

        if (empty($errors)) {
            $this->info('✓ All navigation routes are valid!');

            return self::SUCCESS;
        }

        $this->error('✗ Found '.count($errors).' invalid route(s):');
        foreach ($errors as $error) {
            $this->error("  - {$error}");
        }

        return self::FAILURE;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, string>  $errors
     */
    protected function validateItems(array $items, string $navName, array &$errors, string $path = ''): void
    {
        foreach ($items as $index => $item) {
            $label = $item['label'] ?? "Item at index {$index}";
            $currentPath = $path ? "{$path} > {$label}" : $label;

            if (isset($item['route'])) {
                if (! Route::has($item['route'])) {
                    $errors[] = "{$navName}: Route '{$item['route']}' not found (at: {$currentPath})";
                }
            }

            if (isset($item['children']) && is_array($item['children'])) {
                $this->validateItems($item['children'], $navName, $errors, $currentPath);
            }
        }
    }
}
