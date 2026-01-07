<?php

declare(strict_types=1);

namespace OffloadProject\Navigation;

use enshrined\svgSanitize\Sanitizer;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OffloadProject\Navigation\Contracts\IconCompilerInterface;
use RuntimeException;

final class IconCompiler implements IconCompilerInterface
{
    private const string ICON_CDN_URL = 'https://cdn.jsdelivr.net/npm/lucide-static@latest/icons/%s.svg';

    /** @var array<string, string> */
    private array $compiledIcons = [];

    private ?Sanitizer $sanitizer = null;

    public function __construct()
    {
        $this->loadCompiledIcons();
        $this->initSanitizer();
    }

    public function compile(string $iconName): string
    {
        if (isset($this->compiledIcons[$iconName])) {
            return $this->compiledIcons[$iconName];
        }

        return $iconName;
    }

    public function compileIcon(string $iconName): ?string
    {
        $url = sprintf(self::ICON_CDN_URL, $iconName);

        try {
            $response = Http::timeout(10)->get($url);

            if ($response->successful()) {
                $svg = $response->body();

                return $this->processSvg($svg);
            }

            Log::warning('Failed to fetch icon from CDN', [
                'icon' => $iconName,
                'status' => $response->status(),
            ]);
        } catch (ConnectionException $e) {
            Log::warning('Connection error fetching icon from CDN', [
                'icon' => $iconName,
                'error' => $e->getMessage(),
            ]);

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
            if ($svg !== null) {
                $compiled[$iconName] = $svg;
            }
        }

        return $compiled;
    }

    /**
     * @param  array<int, string>  $iconNames
     * @param  callable|null  $onProgress  Callback for progress updates: fn(int $completed, int $total)
     * @return array<string, string>
     */
    public function compileAllConcurrent(array $iconNames, ?callable $onProgress = null): array
    {
        if (empty($iconNames)) {
            return [];
        }

        $total = count($iconNames);
        $completed = 0;
        $compiled = [];

        // Process in chunks to avoid overwhelming the server
        $chunks = array_chunk($iconNames, 10);

        foreach ($chunks as $chunk) {
            $responses = Http::pool(fn ($pool) => array_map(
                fn (string $iconName) => $pool
                    ->as($iconName)
                    ->timeout(10)
                    ->get(sprintf(self::ICON_CDN_URL, $iconName)),
                $chunk
            ));

            foreach ($chunk as $iconName) {
                $response = $responses[$iconName] ?? null;

                if ($response !== null && $response->successful()) {
                    $svg = $this->processSvg($response->body());

                    if ($svg !== null) {
                        $compiled[$iconName] = $svg;
                    }
                }

                $completed++;

                if ($onProgress !== null) {
                    $onProgress($completed, $total);
                }
            }
        }

        return $compiled;
    }

    /**
     * @param  array<string, string>  $icons
     */
    public function saveCompiled(array $icons): void
    {
        $path = $this->getCompiledPath();
        $directory = dirname($path);

        if (! is_dir($directory)) {
            if (! mkdir($directory, 0755, true) && ! is_dir($directory)) {
                throw new RuntimeException(sprintf('Directory "%s" was not created', $directory));
            }
        }

        // Use JSON instead of var_export for security (prevents code injection)
        $content = json_encode($icons, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        if ($content === false) {
            throw new RuntimeException('Failed to encode icons to JSON');
        }

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

    /**
     * Process and sanitize SVG content.
     */
    private function processSvg(string $svg): ?string
    {
        // Remove license comment (not needed in output, ISC license)
        $svg = preg_replace('/<!--.*?-->\s*/s', '', $svg) ?? $svg;

        // Sanitize SVG to prevent XSS attacks
        if ($this->sanitizer !== null) {
            $svg = $this->sanitizer->sanitize($svg);

            if ($svg === false || $svg === '') {
                return null;
            }
        }

        // Add data-slot attribute for styling
        return preg_replace('/<svg/', '<svg data-slot="icon"', $svg, 1);
    }

    private function loadCompiledIcons(): void
    {
        $path = $this->getCompiledPath();

        if (! file_exists($path)) {
            return;
        }

        $content = file_get_contents($path);

        if ($content === false) {
            return;
        }

        // Try JSON first (new format)
        $decoded = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $this->compiledIcons = $decoded;

            return;
        }

        // Fallback to PHP format for backwards compatibility (will be migrated on next save)
        if (str_starts_with($content, '<?php')) {
            $this->compiledIcons = require $path;
        }
    }

    private function initSanitizer(): void
    {
        if (class_exists(Sanitizer::class)) {
            $this->sanitizer = new Sanitizer();
            $this->sanitizer->removeRemoteReferences(true);
        }
    }

    private function getCompiledPath(): string
    {
        return config('navigation.icons.compiled_path', storage_path('navigation/icons.json'));
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @param  array<int, string>  $icons
     */
    private function extractIconsRecursive(array $items, array &$icons): void
    {
        foreach ($items as $item) {
            if (isset($item['icon']) && is_string($item['icon'])) {
                $icons[] = $item['icon'];
            }

            if (isset($item['children']) && is_array($item['children'])) {
                $this->extractIconsRecursive($item['children'], $icons);
            }
        }
    }
}
