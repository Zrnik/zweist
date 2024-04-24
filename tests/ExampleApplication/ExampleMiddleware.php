<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests\ExampleApplication;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Zrnik\Zweist\Content\MiddlewareWithContextInterface;
use Zrnik\Zweist\Tests\ExampleSchema\NotASchemaClass;
use Zrnik\Zweist\Tests\ExampleSchema\SchemaClass;

class ExampleMiddleware implements MiddlewareInterface, MiddlewareWithContextInterface
{
    public const CONTEXT_HEADER_NAME = 'X-Test-Middleware-Context';

    public const VALUE_HEADER_NAME = 'X-Test-Middleware-Called';

    public const VALUE_HEADER_VALUE = 'true';

    private mixed $context;

    // @phpstan-ignore-next-line
    public function __construct(NotASchemaClass $notASchemaClass, SchemaClass|NotASchemaClass $something)
    {
    }

    public function setContext(mixed $context): void
    {
        $this->context = $context;
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface
    {
        $response = $handler->handle($request);

        $response = $response->withHeader(
            self::VALUE_HEADER_NAME,
            self::VALUE_HEADER_VALUE
        );

        if ($this->context !== null) {
            $response = $response->withHeader(
                self::CONTEXT_HEADER_NAME,
                $this->context,
            );
        }

        return $response;
    }
}
