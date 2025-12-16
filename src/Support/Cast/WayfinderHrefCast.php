<?php

declare(strict_types=1);

namespace OffloadProject\Navigation\Support\Cast;

use Spatie\LaravelData\Casts\Cast;
use Spatie\LaravelData\Data;
use Spatie\LaravelData\Support\Creation\CreationContext;
use Spatie\LaravelData\Support\DataProperty;

final class WayfinderHrefCast implements Cast
{
    /**
     * @param  array<string, mixed>  $properties
     * @param  CreationContext<Data>  $context
     */
    public function cast(DataProperty $property, mixed $value, array $properties, CreationContext $context): mixed
    {
        $method = $properties['method'] ?? null;

        if ($method) {
            return [
                'url' => $value,
                'method' => $method,
            ];
        }

        return $value;
    }
}
