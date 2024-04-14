<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Content;

interface MiddlewareWithContextInterface
{
    public function setContext(mixed $context): void;
}
