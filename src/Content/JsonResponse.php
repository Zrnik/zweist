<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Content;

use JsonException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;

class JsonResponse
{
    /**
     * @throws JsonException
     */
    public static function of(
        ResponseInterface $response,
        object $responseSchema,
    ): ResponseInterface
    {
        $psr17Factory = new Psr17Factory();
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withBody(
                $psr17Factory->createStream(
                    json_encode($responseSchema, JSON_THROW_ON_ERROR)
                )
            );
    }
}