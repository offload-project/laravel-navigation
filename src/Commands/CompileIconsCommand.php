<?php

declare(strict_types=1);

namespace OffloadProject\Navigation\Commands;

use Illuminate\Console\Command;
use OffloadProject\Navigation\IconCompiler;

final class CompileIconsCommand extends Command
{
    protected $signature = 'navigation:compile-icons';

    protected $description = 'Compile Lucide icons used in navigation to SVG strings';

    public function handle(): int
    {
        $config = config('navigation.navigations', []);
        $compiler = new IconCompiler();

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

        $compiled = [];
        foreach ($icons as $iconName) {
            $svg = $compiler->compileIcon($iconName);
            if ($svg) {
                $compiled[$iconName] = $svg;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $compiler->saveCompiled($compiled);

        $this->info('Successfully compiled '.count($compiled).' icons.');
        $this->info('Saved to: '.config('navigation.icons.compiled_path', storage_path('navigation/icons.php')));

        return self::SUCCESS;
    }
}
