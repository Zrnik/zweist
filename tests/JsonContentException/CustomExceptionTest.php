<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests\JsonContentException;

use JsonException;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\AssertionFailedError;
use function PHPUnit\Framework\assertSame;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\RequestInterface;
use stdClass;
use Zrnik\PHPUnit\Exceptions;
use Zrnik\Zweist\Content\Exception\JsonRequestException;
use Zrnik\Zweist\Content\Exception\JsonResponseException;
use Zrnik\Zweist\Content\JsonContentFacade;
use Zrnik\Zweist\Tests\JsonContentException\Example\ExampleSchemaToHydrate;

class CustomExceptionTest extends TestCase
{
    use Exceptions;

    private JsonContentFacade $jsonContentFacade;

    protected function setUp(): void
    {
        $this->jsonContentFacade = new JsonContentFacade(
            new Psr17Factory(),
            new Psr17Factory(),
        );
    }

    private static function createRequestWithJsonBody(mixed $body): RequestInterface
    {
        try {
            return (new Psr17Factory())->createRequest(
                'GET',
                '/example'
            )->withBody(
                (new Psr17Factory())->createStream(
                    json_encode($body, JSON_THROW_ON_ERROR)
                )
            );
        } catch (JsonException) {
            throw new AssertionFailedError(
                'You must provide a valid JSON in the `createRequestWithJsonBody` method.'
            );
        }
    }

    /**
     * @return array<string, mixed>
     */
    private static function createValidJson(): array
    {
        return [
            'string' => 'string',
            'int' => 1,
            'bool' => true,
            'object' => [
                'string' => 'string',
                'int' => 2,
                'bool' => true,
                'nullable' => null,
            ],
            'nullable' => 'nullable',
            'default' => 'not-default',
        ];
    }

    public function testHydrationNoErrorAndIsValid(): void
    {
        /** @var ExampleSchemaToHydrate $hydratedClass */
        $hydratedClass = $this->assertNoExceptionThrown(
            fn() => $this->jsonContentFacade->parseRequest(
                self::createRequestWithJsonBody(self::createValidJson()),
                ExampleSchemaToHydrate::class,
            )
        );

        $this->assertSame($hydratedClass->int, 1);
        $this->assertSame($hydratedClass->string, 'string');
        $this->assertTrue($hydratedClass->bool);
        $this->assertSame($hydratedClass->nullable, 'nullable');
        $this->assertSame($hydratedClass->default, 'not-default');

        $this->assertSame($hydratedClass->object->int, 2);
        $this->assertSame($hydratedClass->object->string, 'string');
        $this->assertTrue($hydratedClass->object->bool);
        $this->assertNull($hydratedClass->object->nullable);
    }

    public function assertStringsAndIntegersAreConverted(): void
    {
        $exampleRequest = self::createValidJson();
        $exampleRequest['int'] = '1';
        $exampleRequest['string'] = 666;

        /** @var ExampleSchemaToHydrate $hydratedClass */
        $hydratedClass = $this->assertNoExceptionThrown(
            fn() => $this->jsonContentFacade->parseRequest(
                self::createRequestWithJsonBody($exampleRequest),
                ExampleSchemaToHydrate::class,
            )
        );

        $this->assertSame($hydratedClass->int, 1);
        $this->assertSame($hydratedClass->string, '666');
    }

    public function testMissingFieldException(): void
    {
        $exampleRequest = self::createValidJson();
        unset($exampleRequest['int']);

        /** @var JsonRequestException $exception */
        $exception = $this->assertExceptionThrown(
            JsonRequestException::class,
            fn() => $this->jsonContentFacade->parseRequest(
                self::createRequestWithJsonBody($exampleRequest),
                ExampleSchemaToHydrate::class,
            )
        );

        assertSame(
            [
                'Field "int" missing.',
            ],
            $exception->getContentErrors()
        );
    }

    public function testTypeErrorException(): void
    {
        $exampleRequest = self::createValidJson();
        $exampleRequest['string'] = false;

        /** @var JsonRequestException $exception */
        $exception = $this->assertExceptionThrown(
            JsonRequestException::class,
            fn() => $this->jsonContentFacade->parseRequest(
                self::createRequestWithJsonBody($exampleRequest),
                ExampleSchemaToHydrate::class,
            )
        );

        assertSame(
            [
                'Argument #1 ($string) must be of type string, bool given.',
            ],
            $exception->getContentErrors()
        );
    }

    public function testEmptyResponse(): void
    {
        /** @var JsonRequestException $exception */
        $exception = $this->assertExceptionThrown(
            JsonRequestException::class,
            fn() => $this->jsonContentFacade->parseRequest(
                self::createRequestWithJsonBody([])->withBody((new Psr17Factory())->createStream()),
                ExampleSchemaToHydrate::class,
            )
        );

        assertSame(
            'Value provided is not a valid JSON.',
            $exception->getMessage(),
        );
    }

    public function testResponseErrorThrownOnRecursion(): void
    {
        $recurseObject = new stdClass();
        $recurseObject->self = $recurseObject;

        /** @var JsonResponseException $exception */
        $exception = $this->assertExceptionThrown(
            JsonResponseException::class,
            fn() => $this->jsonContentFacade->createResponse($recurseObject)
        );

        $this->assertSame('Recursion detected', $exception->getMessage());

        $response = (new Psr17Factory())->createResponse();

        /** @var JsonResponseException $exception */
        $exception = $this->assertExceptionThrown(
            JsonResponseException::class,
            fn() => $this->jsonContentFacade->updateResponse($response, $recurseObject)
        );

        $this->assertSame('Recursion detected', $exception->getMessage());
    }
}
