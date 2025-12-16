<?php

declare(strict_types=1);

namespace OffloadProject\Navigation;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use OffloadProject\Navigation\Contracts\IconCompilerInterface;
use RuntimeException;

final class IconCompiler implements IconCompilerInterface
{
    /** @var array<string, string> */
    private array $compiledIcons = [];

    public function __construct()
    {
        $this->loadCompiledIcons();
    }

    public function compile(string $iconName): string
    {
        // Check if already compiled
        if (isset($this->compiledIcons[$iconName])) {
            return $this->compiledIcons[$iconName];
        }

        // Return icon name if not compiled (can be handled on frontend)
        return $iconName;
    }

    public function compileIcon(string $iconName): ?string
    {
        $url = "https://cdn.jsdelivr.net/npm/lucide-static@latest/icons/{$iconName}.svg";

        try {
            $response = Http::get($url);

            if ($response->successful()) {
                $svg = $response->body();

                // Remove license comment (not needed in output, ISC license)
                $svg = preg_replace('/<!--.*?-->\s*/s', '', $svg);

                return preg_replace('/<svg/', '<svg data-slot="icon"', $svg, 1);
            }
        } catch (ConnectionException) {
            return null;
        }

        return null;
    }

    /**
     * @param  array<int, string>  $iconNames
     * @return array<string, string>
     */
    public function compileAll(array $iconNames): array
    {
        $compiled = [];

        foreach ($iconNames as $iconName) {
            $svg = $this->compileIcon($iconName);
            if ($svg) {
                $compiled[$iconName] = $svg;
            }
        }

        return $compiled;
    }

    /**
     * @param  array<string, string>  $icons
     */
    public function saveCompiled(array $icons): void
    {
        $path = config('navigation.icons.compiled_path', storage_path('navigation/icons.php'));
        $directory = dirname($path);

        if (! is_dir($directory)) {
            if (! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $directory));
            }
        }

        $content = "<?php\n\nreturn ".var_export($icons, true).";\n";
        file_put_contents($path, $content);

        $this->compiledIcons = $icons;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<int, string>
     */
    public function extractIconsFromConfig(array $config): array
    {
        $icons = [];

        foreach ($config as $navigation) {
            $this->extractIconsRecursive($navigation, $icons);
        }

        return array_unique($icons);
    }

    private function loadCompiledIcons(): void
    {
        $path = config('navigation.icons.compiled_path', storage_path('navigation/icons.php'));

        if (file_exists($path)) {
            $this->compiledIcons = require $path;
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, mixed>  $icons
     */
    private function extractIconsRecursive(array $items, array &$icons): void
    {
        foreach ($items as $item) {
            if (isset($item['icon'])) {
                $icons[] = $item['icon'];
            }

            if (isset($item['children']) && is_array($item['children'])) {
                $this->extractIconsRecursive($item['children'], $icons);
            }
        }
    }
}
