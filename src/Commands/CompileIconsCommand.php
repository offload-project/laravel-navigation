<?php

declare(strict_types=1);

namespace OffloadProject\Navigation\Commands;

use Illuminate\Console\Command;
use OffloadProject\Navigation\Contracts\IconCompilerInterface;

final class CompileIconsCommand extends Command
{
    protected $signature = 'navigation:compile-icons {--sequential : Compile icons sequentially instead of concurrently}';

    protected $description = 'Compile Lucide icons used in navigation to SVG strings';

    public function handle(IconCompilerInterface $compiler): int
    {
        $config = config('navigation.navigations', []);

        $this->info('Extracting icons from navigation config...');
        $icons = $compiler->extractIconsFromConfig($config);

        if (empty($icons)) {
            $this->warn('No icons found in navigation configuration.');

            return self::SUCCESS;
        }

        $this->info('Found '.count($icons).' unique icons.');
        $this->info('Compiling icons...');

        $bar = $this->output->createProgressBar(count($icons));
        $bar->start();

        if ($this->option('sequential')) {
            $compiled = $this->compileSequentially($compiler, $icons, $bar);
        } else {
            $compiled = $compiler->compileAllConcurrent(
                $icons,
                fn () => $bar->advance()
            );
        }

        $bar->finish();
        $this->newLine();

        $compiler->saveCompiled($compiled);

        $this->info('Successfully compiled '.count($compiled).' icons.');
        $this->info('Saved to: '.config('navigation.icons.compiled_path', storage_path('navigation/icons.json')));

        if (count($compiled) < count($icons)) {
            $failed = array_diff($icons, array_keys($compiled));
            $this->warn('Failed to compile '.count($failed).' icons: '.implode(', ', $failed));
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<int, string>  $icons
     * @return array<string, string>
     */
    private function compileSequentially(
        IconCompilerInterface $compiler,
        array $icons,
        \Symfony\Component\Console\Helper\ProgressBar $bar
    ): array {
        $compiled = [];

        foreach ($icons as $iconName) {
            $svg = $compiler->compileIcon($iconName);

            if ($svg !== null) {
                $compiled[$iconName] = $svg;
            }

            $bar->advance();
        }

        return $compiled;
    }
}
