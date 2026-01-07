<?php

declare(strict_types=1);

namespace OffloadProject\Navigation\Contracts;

interface IconCompilerInterface
{
    /**
     * Compile an icon name to its SVG representation.
     * Returns the compiled SVG if available, otherwise returns the icon name.
     */
    public function compile(string $iconName): string;

    /**
     * Compile a single icon from the CDN.
     */
    public function compileIcon(string $iconName): ?string;

    /**
     * Compile multiple icons sequentially.
     *
     * @param  array<int, string>  $iconNames
     * @return array<string, string>
     */
    public function compileAll(array $iconNames): array;

    /**
     * Compile multiple icons concurrently using HTTP pool.
     *
     * @param  array<int, string>  $iconNames
     * @param  callable|null  $onProgress  Callback for progress updates: fn(int $completed, int $total)
     * @return array<string, string>
     */
    public function compileAllConcurrent(array $iconNames, ?callable $onProgress = null): array;

    /**
     * Save compiled icons to storage.
     *
     * @param  array<string, string>  $icons
     */
    public function saveCompiled(array $icons): void;

    /**
     * Extract all icon names from a navigation config.
     *
     * @param  array<string, mixed>  $config
     * @return array<int, string>
     */
    public function extractIconsFromConfig(array $config): array;
}
