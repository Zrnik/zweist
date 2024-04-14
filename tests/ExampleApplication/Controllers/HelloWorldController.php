<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests\ExampleApplication\Controllers;

use JsonException;
use Nyholm\Psr7\Factory\Psr17Factory;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Middleware;
use OpenApi\Attributes\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zrnik\Zweist\Content\JsonContentFacade;
use Zrnik\Zweist\Tests\ExampleApplication\ExampleClassMiddleware;
use Zrnik\Zweist\Tests\ExampleApplication\ExampleMiddleware;

#[Middleware(ExampleClassMiddleware::class)]
class HelloWorldController
{
    private JsonContentFacade $jsonContentFacade;

    public function __construct()
    {
        $this->jsonContentFacade = new JsonContentFacade(
            new Psr17Factory(),
            new Psr17Factory(),
        );
    }

    /**
     * @param array<string, string> $arguments
     * @throws JsonException
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
        ),
        Middleware(ExampleMiddleware::class),
        Middleware(ExampleMiddleware::class, [
            'context' => 'value',
        ]),
    ]
    public function sayHello(
        RequestInterface $request,
        ResponseInterface $response,
        array $arguments = []
    ): ResponseInterface
    {
        return $this->jsonContentFacade->updateResponse(
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
