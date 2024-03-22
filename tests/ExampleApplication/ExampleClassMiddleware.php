<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests\ExampleApplication;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ExampleClassMiddleware implements MiddlewareInterface
{
    public const HEADER_NAME = 'X-Test-Class-Middleware-Called';

    public const HEADER_VALUE = 'true';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request)->withHeader(self::HEADER_NAME, self::HEADER_VALUE);
    }
}
