<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Content;

use EventSauce\ObjectHydrator\ObjectMapperUsingReflection;
use EventSauce\ObjectHydrator\UnableToHydrateObject;
use JsonException;
use Psr\Http\Message\RequestInterface;

class JsonRequest
{
    /**
     * @template T of object
     * @param RequestInterface $request
     * @param class-string<T> $requestSchema
     * @return T
     * @throws JsonException
     * @throws UnableToHydrateObject
     */
    public static function of(
        RequestInterface $request,
        string $requestSchema,
    ): mixed
    {
        /** @var T */
        return (new ObjectMapperUsingReflection())->hydrateObject(
            $requestSchema,
            json_decode(
                (string) $request->getBody(),
                true,
                512,
                JSON_THROW_ON_ERROR
            ),
        );
    }
}
