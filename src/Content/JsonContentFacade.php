<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Content;

use EventSauce\ObjectHydrator\DefinitionProvider;
use EventSauce\ObjectHydrator\KeyFormatterWithoutConversion;
use EventSauce\ObjectHydrator\ObjectMapperUsingReflection;
use EventSauce\ObjectHydrator\UnableToHydrateObject;
use JsonException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;
use TypeError;
use Zrnik\Zweist\Content\Exception\JsonContentException;
use Zrnik\Zweist\Content\Exception\JsonRequestException;
use Zrnik\Zweist\Content\Exception\JsonResponseException;
use Zrnik\Zweist\Content\Pagination\PaginationData;

class JsonContentFacade
{
    public function __construct(
        private readonly StreamFactoryInterface $streamFactory,
        private readonly ResponseFactoryInterface $responseFactory,
    )
    {
    }

    /**
     * @template T of object
     * @param RequestInterface $request
     * @param class-string<T> $className
     * @return T
     * @throws JsonContentException
     * @noinspection PhpDocSignatureInspection
     */
    public function parseRequest(
        RequestInterface $request,
        string $className,
    ): object
    {
        try {
            return $this->hydrateObject(
                $className,
                json_decode(
                    (string) $request->getBody(),
                    true,
                    512,
                    JSON_THROW_ON_ERROR
                ),
            );
        } catch (JsonException $jsonException) {
            throw JsonRequestException::fromJsonException($jsonException);
        }
    }

    /**
     * @template T of object
     * @param class-string<T> $className
     * @param array<mixed> $data
     * @return T
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     * @noinspection PhpDocSignatureInspection
     */
    public function hydrateObject(string $className, array $data): object
    {
        try {
            /** @var T */
            return (new ObjectMapperUsingReflection(
                new DefinitionProvider(
                    keyFormatter: new KeyFormatterWithoutConversion(),
                ),
            ))->hydrateObject(
                $className,
                $data,
            );
        } catch (UnableToHydrateObject $unableToHydrateObject) {

            $previous = $unableToHydrateObject->getPrevious();

            if ($previous === null) {
                // Valid json, but missing fields...
                throw JsonRequestException::fromUnableToHydrateException($unableToHydrateObject);
            }

            if ($previous instanceof TypeError) {
                // Invalid types in the json...
                throw JsonRequestException::fromJsonTypeError($previous);
            }

            throw JsonRequestException::unhandledObjectHydrate($unableToHydrateObject); // @codeCoverageIgnore
        } catch (Throwable $throwable) { // @codeCoverageIgnoreStart
            throw JsonRequestException::unhandledThrowable($throwable);
        } // @codeCoverageIgnoreEnd
    }

    /**
     * @param object|array<mixed>|scalar|null $responseSchema
     * @throws JsonContentException
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public function updateResponse(
        ResponseInterface $response,
        object|array|string|int|float|bool|null $responseSchema,
        int $statusCode = 200,
    ): ResponseInterface
    {
        try {
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus($statusCode)
                ->withBody(
                    $this->streamFactory->createStream(
                        json_encode($responseSchema, JSON_THROW_ON_ERROR)
                    )
                );
        } catch (JsonException $jsonException) {
            throw JsonResponseException::fromJsonException($jsonException);
        }
    }

    /**
     * @param object|array<mixed>|scalar|null $responseSchema
     * @throws JsonContentException
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public function createResponse(
        object|array|string|int|float|bool|null $responseSchema,
        int $statusCode = 200,
    ): ResponseInterface
    {
        return $this->updateResponse($this->responseFactory->createResponse(), $responseSchema, $statusCode);
    }

    /**
     * You must provide already paginated items!
     *
     * @param array<object|array<mixed>|scalar|null> $filteredItems
     * @throws JsonContentException
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public function createPaginatedResponse(
        int $page,
        int $itemsPerPage,
        int $itemCount,
        array $filteredItems,
        int $statusCode = 200,
    ): ResponseInterface
    {
        return $this->updatePaginatedResponse(
            $this->responseFactory->createResponse(),
            $page,
            $itemsPerPage,
            $itemCount,
            $filteredItems,
            $statusCode,
        );
    }

    /**
     * You must provide already paginated items!
     *
     * @param array<object|array<mixed>|scalar|null> $filteredItems
     * @throws JsonContentException
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public function updatePaginatedResponse(
        ResponseInterface $response,
        int $page,
        int $itemsPerPage,
        int $itemCount,
        array $filteredItems,
        int $statusCode = 200,
    ): ResponseInterface
    {
        return $this->updateResponse(
            $this->responseFactory->createResponse(),
            [
                'pagination' => new PaginationData($page, $itemsPerPage, $itemCount),
                'data' => $filteredItems,
            ],
            $statusCode
        );
    }
}
