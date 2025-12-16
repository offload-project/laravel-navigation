<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;

it('compiles icons from navigation config', function () {
    config(['navigation' => $this->getTestConfig()]);

    Http::fake([
        'cdn.jsdelivr.net/*' => Http::response('<svg>icon</svg>', 200),
    ]);

    $this->artisan('navigation:compile-icons')
        ->expectsOutput('Extracting icons from navigation config...')
        ->assertExitCode(0);
});

it('handles missing icons gracefully', function () {
    config(['navigation' => $this->getTestConfig()]);

    Http::fake([
        'cdn.jsdelivr.net/*' => Http::response('Not found', 404),
    ]);

    $this->artisan('navigation:compile-icons')
        ->assertExitCode(0);
});

it('shows warning when no icons found', function () {
    config(['navigation' => ['navigations' => ['empty' => []]]]);

    $this->artisan('navigation:compile-icons')
        ->expectsOutput('No icons found in navigation configuration.')
        ->assertExitCode(0);
});
