<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Content;

use JsonException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\ResponseInterface;

class JsonResponse
{
    /**
     * @param object|array<mixed>|scalar|null $responseSchema
     * @throws JsonException
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public static function of(
        ResponseInterface $response,
        object|array|string|int|float|bool|null $responseSchema,
        int $statusCode = 200,
    ): ResponseInterface
    {
        $psr17Factory = new Psr17Factory();
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode)
            ->withBody(
                $psr17Factory->createStream(
                    json_encode($responseSchema, JSON_THROW_ON_ERROR)
                )
            );
    }

    /**
     * @param object|array<mixed>|scalar|null $responseSchema
     * @throws JsonException
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public static function new(
        object|array|string|int|float|bool|null $responseSchema,
        int $statusCode = 200,
    ): ResponseInterface
    {
        $psr17Factory = new Psr17Factory();
        return $psr17Factory
            ->createResponse()
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($statusCode)
            ->withBody(
                $psr17Factory->createStream(
                    json_encode($responseSchema, JSON_THROW_ON_ERROR)
                )
            );
    }
}