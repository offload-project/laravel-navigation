<?php

declare(strict_types=1);

namespace OffloadProject\Navigation\Support;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use OffloadProject\Navigation\Data\NavigationItem;

final class ItemVisibilityResolver
{
    /**
     * Check if a navigation item should be visible.
     */
    public function isVisible(NavigationItem $item): bool
    {
        if (! $this->passesVisibilityCheck($item)) {
            return false;
        }

        if (! $this->passesGateCheck($item)) {
            return false;
        }

        return true;
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

        if (! $user) {
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
        return auth()->user();
    }
}
