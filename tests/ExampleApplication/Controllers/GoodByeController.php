<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests\ExampleApplication\Controllers;

use JsonException;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Post;
use OpenApi\Attributes\RequestBody;
use OpenApi\Attributes\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Zrnik\Zweist\Content\JsonRequest;
use Zrnik\Zweist\Content\JsonResponse;

class GoodByeController
{
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
        $goodByeRequestParameters = JsonRequest::of(
            $request,
            GoodByeRequestParameters::class,
        );

        return JsonResponse::of(
            $response,
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