<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests\ExampleApplication\Controllers;

use JsonException;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Middleware;
use OpenApi\Attributes\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zrnik\Zweist\Content\JsonResponse;
use Zrnik\Zweist\Tests\ExampleApplication\ExampleMiddleware;

class HelloWorldController
{
    /**
     * @throws JsonException
     * @param array<string, string> $arguments
     */
    #[
        Get(
            path: '/api/hello/{name}',
            operationId: 'Say Hello',
            description: 'says hello by the request parameter',
        ),
        Response(
            response: 200,
            description: 'when ok',
            content: new JsonContent(ref: TestResponse::class)
        )
    ]
    #[Middleware(ExampleMiddleware::class)]
    public function sayHello(
        RequestInterface $request,
        ResponseInterface $response,
        array $arguments = []
    ): ResponseInterface
    {
        return JsonResponse::of(
            $response,
            new TestResponse(
                sprintf(
                    'Hello, %s :)',
                    $arguments['name']
                )
            )
        );
    }
}
