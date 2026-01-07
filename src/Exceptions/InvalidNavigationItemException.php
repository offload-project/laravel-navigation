<?php

declare(strict_types=1);

namespace OffloadProject\Navigation\Exceptions;

use InvalidArgumentException;

/**
 * Exception thrown when a navigation item configuration is invalid.
 *
 * Provides helpful error messages with:
 * - Clear description of what went wrong
 * - Suggestion on how to fix it
 * - Link to relevant documentation
 */
final class InvalidNavigationItemException extends InvalidArgumentException
{
    private const DOCS_BASE_URL = 'https://github.com/offload-project/laravel-navigation';

    /**
     * @param  array<string, mixed>  $item
     */
    public function __construct(
        string $message,
        private readonly array $item = [],
        private readonly ?string $suggestion = null,
        private readonly ?string $docsSection = null
    ) {
        $fullMessage = $message;

        if ($this->suggestion !== null) {
            $fullMessage .= ' '.$this->suggestion;
        }

        if ($this->docsSection !== null) {
            $fullMessage .= ' See: '.self::DOCS_BASE_URL.$this->docsSection;
        }

        parent::__construct($fullMessage);
    }

    /**
     * Thrown when an item has neither label, children, nor custom metadata.
     *
     * @param  array<string, mixed>  $item
     */
    public static function missingContent(array $item): self
    {
        return new self(
            message: 'Navigation item must have a label, children, or custom metadata.',
            item: $item,
            suggestion: 'Add a "label" key with display text, "children" array, or custom keys like "type" for separators.',
            docsSection: '#configuration'
        );
    }

    /**
     * Thrown when an item has both route and url.
     *
     * @param  array<string, mixed>  $item
     */
    public static function bothRouteAndUrl(array $item): self
    {
        return new self(
            message: 'Navigation item cannot have both "route" and "url".',
            item: $item,
            suggestion: 'Use "route" for internal Laravel routes (e.g., "users.index") or "url" for external links (e.g., "https://docs.example.com"), but not both.',
            docsSection: '#routing'
        );
    }

    /**
     * Thrown when an item has both breadcrumbOnly and navOnly.
     *
     * @param  array<string, mixed>  $item
     */
    public static function conflictingVisibility(array $item): self
    {
        return new self(
            message: 'Navigation item cannot be both "breadcrumbOnly" and "navOnly".',
            item: $item,
            suggestion: 'Use "breadcrumbOnly" for items that should only appear in breadcrumbs (like edit pages), or "navOnly" for items that should only appear in navigation menus, but not both.',
            docsSection: '#breadcrumbs'
        );
    }

    /**
     * Thrown when params is used without a route.
     *
     * @param  array<string, mixed>  $item
     */
    public static function paramsWithoutRoute(array $item): self
    {
        return new self(
            message: 'Navigation item "params" can only be used with a "route".',
            item: $item,
            suggestion: 'Either add a "route" key, or remove the "params" key. Params are used for route parameter matching and URL generation.',
            docsSection: '#route-parameters'
        );
    }

    /**
     * Thrown when an invalid HTTP method is specified.
     *
     * @param  array<string, mixed>  $item
     * @param  array<int, string>  $validMethods
     */
    public static function invalidMethod(array $item, string $method, array $validMethods): self
    {
        return new self(
            message: sprintf('Invalid HTTP method "%s".', $method),
            item: $item,
            suggestion: sprintf('Valid methods are: %s. Use "post" for form submissions, "delete" for destructive actions.', implode(', ', $validMethods)),
            docsSection: '#action-items'
        );
    }

    /**
     * Get the invalid item configuration.
     *
     * @return array<string, mixed>
     */
    public function getItem(): array
    {
        return $this->item;
    }

    /**
     * Get the suggestion for fixing the error.
     */
    public function getSuggestion(): ?string
    {
        return $this->suggestion;
    }

    /**
     * Get the documentation URL.
     */
    public function getDocsUrl(): ?string
    {
        return $this->docsSection !== null
            ? self::DOCS_BASE_URL.$this->docsSection
            : null;
    }
}
