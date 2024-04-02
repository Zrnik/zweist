<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests\ExampleApplication\Controllers;

use JsonException;
use Nyholm\Psr7\Factory\Psr17Factory;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use OpenApi\Attributes\RequestBody;
use OpenApi\Attributes\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zrnik\Zweist\Content\JsonContentFacade;

class GoodByeController
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
     * @throws JsonException
     */
    #[
        Post(
            path: '/api/goodbye',
            operationId: 'Say GoodBye',
            description: 'says goodbye by the body parameter',
        ),
        RequestBody(
            content: new JsonContent(ref: GoodByeRequestParameters::class)
        ),
        Response(
            response: 200,
            description: 'when ok',
            content: new JsonContent(ref: TestResponse::class)
        )
    ]
    public function sayGoodbye(
        RequestInterface $request,
        ResponseInterface $response
    ): ResponseInterface
    {
        /** @var GoodByeRequestParameters $goodByeRequestParameters */
        $goodByeRequestParameters = $this->jsonContentFacade->parseRequest(
            $request,
            GoodByeRequestParameters::class,
        );

        return $this->jsonContentFacade->createResponse(
            new TestResponse(
                sprintf(
                    '%s, %s :(',
                    $goodByeRequestParameters->style->text,
                    $goodByeRequestParameters->name,
                )
            )
        );
    }
}
