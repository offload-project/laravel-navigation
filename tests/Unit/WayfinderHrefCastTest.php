<?php

declare(strict_types=1);

use OffloadProject\Navigation\Support\Cast\WayfinderHrefCast;
use Spatie\LaravelData\Optional;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

beforeEach(function () {
    $this->cast = new WayfinderHrefCast;
    $this->property = Mockery::mock(DataProperty::class);
    $this->context = Mockery::mock(CreationContext::class);
});

it('returns url and method array when method is provided', function () {
    $result = $this->cast->cast(
        $this->property,
        '/dashboard',
        ['method' => 'post'],
        $this->context
    );

    expect($result)->toBe([
        'url' => '/dashboard',
        'method' => 'post',
    ]);
});

it('returns only the url when method is null', function () {
    $result = $this->cast->cast(
        $this->property,
        '/dashboard',
        ['method' => null],
        $this->context
    );

    expect($result)->toBe('/dashboard');
});

it('returns only the url when method key is not present', function () {
    $result = $this->cast->cast(
        $this->property,
        '/dashboard',
        [],
        $this->context
    );

    expect($result)->toBe('/dashboard');
});

it('returns only the url when method is an Optional instance', function () {
    $result = $this->cast->cast(
        $this->property,
        '/dashboard',
        ['method' => new Optional],
        $this->context
    );

    expect($result)->toBe('/dashboard');
});
