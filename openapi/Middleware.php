<?php

declare(strict_types=1);

namespace OpenApi\Attributes;

use Attribute;
use Psr\Http\Server\MiddlewareInterface;

#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_ALL)]
class Middleware
{
    /**
     * @param class-string<MiddlewareInterface> $middlewareClass
     */
    public function __construct(
        public readonly string $middlewareClass,
        public readonly mixed $context = null,
    )
    {
    }
}
