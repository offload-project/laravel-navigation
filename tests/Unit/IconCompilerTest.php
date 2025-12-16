<?php

declare(strict_types=1);

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
    $property->setAccessible(true);
    $property->setValue($compiler, ['home' => '<svg>test</svg>']);

    $result = $compiler->compile('home');

    expect($result)->toBe('<svg>test</svg>');
});
