<?php

declare(strict_types=1);

namespace OffloadProject\Navigation\Support;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use OffloadProject\Navigation\Data\NavigationItem;

final class ItemVisibilityResolver
{
    public function __construct(
        private readonly Guard $auth
    ) {}

    /**
     * Check if a navigation item should be visible.
     */
    public function isVisible(NavigationItem $item): bool
    {
        if (! $this->passesVisibilityCheck($item)) {
            return false;
        }

        return $this->passesGateCheck($item);
    }

    /**
     * Check the 'visible' attribute condition.
     */
    private function passesVisibilityCheck(NavigationItem $item): bool
    {
        if ($item->visible === null) {
            return true;
        }

        if ($item->visible instanceof Closure) {
            return (bool) ($item->visible)();
        }

        return (bool) $item->visible;
    }

    /**
     * Check the 'can' gate/policy condition.
     */
    private function passesGateCheck(NavigationItem $item): bool
    {
        if ($item->can === null) {
            return true;
        }

        $user = $this->getUser();

        if ($user === null) {
            return false;
        }

        if (is_array($item->can)) {
            [$ability, $arguments] = $item->can;

            return $user->can($ability, $arguments);
        }

        return $user->can($item->can);
    }

    /**
     * Get the authenticated user.
     */
    private function getUser(): ?Authenticatable
    {
        return $this->auth->user();
    }
}
