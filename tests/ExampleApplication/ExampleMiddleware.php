<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests\ExampleApplication;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zrnik\Zweist\Content\MiddlewareWithContextInterface;

class ExampleMiddleware implements MiddlewareInterface, MiddlewareWithContextInterface
{
    public const CONTEXT_HEADER_NAME = 'X-Test-Middleware-Context';

    public const VALUE_HEADER_NAME = 'X-Test-Middleware-Called';

    public const VALUE_HEADER_VALUE = 'true';

    private mixed $context;

    public function setContext(mixed $context): void
    {
        $this->context = $context;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
        return $handler
            ->handle($request)
            ->withHeader(
                self::VALUE_HEADER_NAME,
                self::VALUE_HEADER_VALUE
            )->withHeader(
                self::CONTEXT_HEADER_NAME,
                $this->context,
            );
    }
}
