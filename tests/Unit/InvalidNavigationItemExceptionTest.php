<?php

declare(strict_types=1);

use OffloadProject\Navigation\Data\NavigationItem;
use OffloadProject\Navigation\Exceptions\InvalidNavigationItemException;

describe('InvalidNavigationItemException', function (): void {
    it('throws for missing content', function (): void {
        NavigationItem::fromArray([]);
    })->throws(InvalidNavigationItemException::class, 'must have a label');

    it('throws for both route and url', function (): void {
        NavigationItem::fromArray([
            'label' => 'Test',
            'route' => 'test.route',
            'url' => 'https://example.com',
        ]);
    })->throws(InvalidNavigationItemException::class, 'cannot have both "route" and "url"');

    it('throws for conflicting visibility', function (): void {
        NavigationItem::fromArray([
            'label' => 'Test',
            'breadcrumbOnly' => true,
            'navOnly' => true,
        ]);
    })->throws(InvalidNavigationItemException::class, 'cannot be both "breadcrumbOnly" and "navOnly"');

    it('throws for params without route', function (): void {
        NavigationItem::fromArray([
            'label' => 'Test',
            'params' => ['id' => 1],
        ]);
    })->throws(InvalidNavigationItemException::class, '"params" can only be used with a "route"');

    it('throws for invalid method', function (): void {
        NavigationItem::fromArray([
            'label' => 'Test',
            'route' => 'test.route',
            'method' => 'invalid',
        ]);
    })->throws(InvalidNavigationItemException::class, 'Invalid HTTP method');

    it('includes valid methods in error message for invalid method', function (): void {
        try {
            NavigationItem::fromArray([
                'label' => 'Test',
                'route' => 'test.route',
                'method' => 'invalid',
            ]);
        } catch (InvalidNavigationItemException $e) {
            expect($e->getMessage())->toContain('get, post, put, patch, delete');
        }
    });

    it('includes item config in exception', function (): void {
        $exception = InvalidNavigationItemException::missingContent(['foo' => 'bar']);

        expect($exception->getItem())->toBe(['foo' => 'bar']);
    });

    it('includes suggestion in exception', function (): void {
        $exception = InvalidNavigationItemException::missingContent([]);

        expect($exception->getSuggestion())->toContain('label');
    });

    it('includes docs URL in exception', function (): void {
        $exception = InvalidNavigationItemException::missingContent([]);

        expect($exception->getDocsUrl())->toContain('github.com');
    });

    it('full message includes suggestion and docs link', function (): void {
        $exception = InvalidNavigationItemException::bothRouteAndUrl([
            'label' => 'Test',
            'route' => 'test',
            'url' => 'https://example.com',
        ]);

        $message = $exception->getMessage();

        expect($message)->toContain('cannot have both');
        expect($message)->toContain('Use "route" for internal');
        expect($message)->toContain('See: https://github.com');
    });

    it('accepts valid methods', function (): void {
        $validMethods = ['get', 'post', 'put', 'patch', 'delete'];

        foreach ($validMethods as $method) {
            $item = NavigationItem::fromArray([
                'label' => 'Test',
                'route' => 'test.route',
                'method' => $method,
            ]);

            expect($item->method)->toBe($method);
        }
    });

    it('normalizes method to lowercase', function (): void {
        $item = NavigationItem::fromArray([
            'label' => 'Test',
            'route' => 'test.route',
            'method' => 'POST',
        ]);

        expect($item->method)->toBe('post');
    });
});
