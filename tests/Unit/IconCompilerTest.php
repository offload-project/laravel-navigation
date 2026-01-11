<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use OffloadProject\Navigation\IconCompiler;

it('extracts icons from config', function () {
    $config = [
        'main' => [
            ['label' => 'Home', 'icon' => 'home'],
            ['label' => 'Users', 'icon' => 'users', 'children' => [
                ['label' => 'Profile', 'icon' => 'user'],
            ]],
        ],
        'footer' => [
            ['label' => 'Settings', 'icon' => 'settings'],
        ],
    ];

    $compiler = new IconCompiler();
    $icons = $compiler->extractIconsFromConfig($config);

    expect($icons)->toContain('home', 'users', 'user', 'settings')
        ->and($icons)->toHaveCount(4);
});

it('removes duplicate icons', function () {
    $config = [
        'main' => [
            ['label' => 'Home', 'icon' => 'home'],
            ['label' => 'Dashboard', 'icon' => 'home'],
        ],
    ];

    $compiler = new IconCompiler();
    $icons = $compiler->extractIconsFromConfig($config);

    expect($icons)->toHaveCount(1);
});

it('returns icon name when not compiled', function () {
    $compiler = new IconCompiler();
    $result = $compiler->compile('home');

    expect($result)->toBe('home');
});

it('returns compiled SVG when available', function () {
    $compiler = new IconCompiler();

    // Mock the compiled icons
    $reflection = new ReflectionClass($compiler);
    $property = $reflection->getProperty('compiledIcons');
    $property->setValue($compiler, ['home' => '<svg>test</svg>']);

    $result = $compiler->compile('home');

    expect($result)->toBe('<svg>test</svg>');
});

describe('SVG Processing', function (): void {
    it('removes HTML comments from SVG', function (): void {
        $compiler = new IconCompiler();
        $reflection = new ReflectionClass($compiler);
        $method = $reflection->getMethod('processSvg');

        $svg = '<!-- License: ISC --><svg xmlns="http://www.w3.org/2000/svg"><path d="M1 1"/></svg>';
        $result = $method->invoke($compiler, $svg);

        expect($result)->not->toContain('<!--');
        expect($result)->toContain('<svg');
    });

    it('adds data-slot attribute to SVG', function (): void {
        $compiler = new IconCompiler();
        $reflection = new ReflectionClass($compiler);
        $method = $reflection->getMethod('processSvg');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><path d="M1 1"/></svg>';
        $result = $method->invoke($compiler, $svg);

        expect($result)->toContain('data-slot="icon"');
    });

    it('sanitizes SVG content', function (): void {
        $compiler = new IconCompiler();
        $reflection = new ReflectionClass($compiler);
        $method = $reflection->getMethod('processSvg');

        // SVG with potentially dangerous content
        $svg = '<svg xmlns="http://www.w3.org/2000/svg"><script>alert("xss")</script><path d="M1 1"/></svg>';
        $result = $method->invoke($compiler, $svg);

        // Script tags should be removed by sanitizer
        expect($result)->not->toContain('<script>');
    });

    it('returns null for invalid SVG', function (): void {
        $compiler = new IconCompiler();
        $reflection = new ReflectionClass($compiler);
        $method = $reflection->getMethod('processSvg');

        // Completely invalid content that sanitizer will reject
        $result = $method->invoke($compiler, '');

        expect($result)->toBeNull();
    });
});

describe('Concurrent Compilation', function (): void {
    it('returns empty array for empty icon list', function (): void {
        $compiler = new IconCompiler();
        $result = $compiler->compileAllConcurrent([]);

        expect($result)->toBe([]);
    });

    it('compiles icons concurrently', function (): void {
        Http::fake([
            'cdn.jsdelivr.net/*' => Http::response('<svg xmlns="http://www.w3.org/2000/svg"><path d="M1 1"/></svg>'),
        ]);

        $compiler = new IconCompiler();
        $result = $compiler->compileAllConcurrent(['home', 'user']);

        expect($result)->toHaveCount(2);
        expect($result)->toHaveKeys(['home', 'user']);
    });

    it('calls progress callback', function (): void {
        Http::fake([
            'cdn.jsdelivr.net/*' => Http::response('<svg xmlns="http://www.w3.org/2000/svg"><path d="M1 1"/></svg>'),
        ]);

        $progressCalls = [];
        $compiler = new IconCompiler();
        $compiler->compileAllConcurrent(['home', 'user'], function ($completed, $total) use (&$progressCalls): void {
            $progressCalls[] = ['completed' => $completed, 'total' => $total];
        });

        expect($progressCalls)->not->toBeEmpty();
        expect($progressCalls[count($progressCalls) - 1]['total'])->toBe(2);
    });

    it('handles failed icon fetches gracefully', function (): void {
        Http::fake([
            'cdn.jsdelivr.net/npm/lucide-static@latest/icons/home.svg' => Http::response('<svg xmlns="http://www.w3.org/2000/svg"><path d="M1 1"/></svg>'),
            'cdn.jsdelivr.net/npm/lucide-static@latest/icons/invalid.svg' => Http::response('Not Found', 404),
        ]);

        $compiler = new IconCompiler();
        $result = $compiler->compileAllConcurrent(['home', 'invalid']);

        expect($result)->toHaveCount(1);
        expect($result)->toHaveKey('home');
        expect($result)->not->toHaveKey('invalid');
    });
});

describe('Single Icon Compilation', function (): void {
    it('fetches and processes icon from CDN', function (): void {
        Http::fake([
            'cdn.jsdelivr.net/*' => Http::response('<svg xmlns="http://www.w3.org/2000/svg"><path d="M1 1"/></svg>'),
        ]);

        $compiler = new IconCompiler();
        $result = $compiler->compileIcon('home');

        expect($result)->toContain('<svg');
        expect($result)->toContain('data-slot="icon"');
    });

    it('returns null for non-existent icon', function (): void {
        Http::fake([
            'cdn.jsdelivr.net/*' => Http::response('Not Found', 404),
        ]);

        $compiler = new IconCompiler();
        $result = $compiler->compileIcon('non-existent-icon');

        expect($result)->toBeNull();
    });

    it('handles connection errors gracefully', function (): void {
        Http::fake(function () {
            throw new Illuminate\Http\Client\ConnectionException('Connection refused');
        });

        $compiler = new IconCompiler();
        $result = $compiler->compileIcon('home');

        expect($result)->toBeNull();
    });
});
