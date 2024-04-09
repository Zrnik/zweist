<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests;

use DI\Container;
use InvalidArgumentException;
use JsonException;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Slim\App;
use Zrnik\PHPUnit\Exceptions;
use Zrnik\Zweist\Content\Pagination\PaginatedJsonResponse;
use Zrnik\Zweist\Content\Pagination\PaginationData;
use Zrnik\Zweist\Exception\MisconfiguredOpenApiGeneratorException;
use Zrnik\Zweist\Tests\ExampleApplication\Controllers\PaginatedObject;
use Zrnik\Zweist\ZweistConfiguration;
use Zrnik\Zweist\ZweistOpenApiGenerator;
use Zrnik\Zweist\ZweistRouteService;

class ZweistTest extends TestCase
{
    use Exceptions;

    private Container $container;

    private ZweistConfiguration $zweistConfiguration;

    private Psr17Factory $psr17Factory;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->psr17Factory = new Psr17Factory();

        $this->container = new Container();

        $this->container->set(LoggerInterface::class, $this->createMock(LoggerInterface::class));

        $this->zweistConfiguration = new ZweistConfiguration(
            [
                __DIR__ . '/ExampleApplication',
            ],
            __DIR__ . '/../temp/OpenApi.json',
            __DIR__ . '/../temp/router.json',
        );
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws JsonException
     * @throws NotFoundExceptionInterface
     */
    public function testRouter(): void
    {
        if (file_exists($this->zweistConfiguration->openApiJsonPath)) {
            unlink($this->zweistConfiguration->openApiJsonPath);
        }

        if (file_exists($this->zweistConfiguration->routerJsonPath)) {
            unlink($this->zweistConfiguration->routerJsonPath);
        }

        self::assertFileDoesNotExist($this->zweistConfiguration->openApiJsonPath);
        self::assertFileDoesNotExist($this->zweistConfiguration->routerJsonPath);

        $zweistOpenApiGenerator = new ZweistOpenApiGenerator($this->zweistConfiguration, $this->container);

        $zweistOpenApiGenerator->generate();

        self::assertFileExists($this->zweistConfiguration->openApiJsonPath);
        self::assertFileExists($this->zweistConfiguration->routerJsonPath);

        $app = new App($this->psr17Factory);

        $zweistRouteService = new ZweistRouteService(
            $this->zweistConfiguration,
            $this->container
        );
        $zweistRouteService->applyRoutes($app);

        foreach (['John', 'Doe'] as $name) {
            $response = $app->handle(
                $this->psr17Factory->createServerRequest(
                    'GET',
                    sprintf('/api/hello/%s', $name)
                )
            );

            self::assertSame(
                sprintf('{"message":"Hello, %s :)"}', $name),
                (string) $response->getBody(),
            );
        }

        foreach (
            [
                'John' => 'Hello',
                'Doe' => 'Hi',
            ]
            as $name => $style
        ) {
            $response = $app->handle(
                $this->psr17Factory->createServerRequest(
                    'POST',
                    '/api/goodbye'
                )->withBody(
                    $this->psr17Factory->createStream(
                        json_encode([
                            'name' => $name,
                            'style' => [
                                'text' => $style,
                            ],
                        ], JSON_THROW_ON_ERROR)
                    )
                )
            );

            self::assertSame(
                sprintf('{"message":"%s, %s :("}', $style, $name),
                (string) $response->getBody(),
            );
        }
    }

    public function testConfigException(): void
    {
        $this->expectException(MisconfiguredOpenApiGeneratorException::class);
        new ZweistConfiguration([], '', '');
    }

    /**
     * @return void
     * @throws JsonException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testFileNotFoundException(): void
    {
        $this->expectException(RuntimeException::class);
        $zweistConfiguration = new ZweistConfiguration([''], '', 'non-existent.json');
        $zweistRouteService = new ZweistRouteService($zweistConfiguration, $this->container);
        $zweistRouteService->applyRoutes(new App(new Psr17Factory()));
    }

    public function testInvalidControllerThrowsException(): void
    {
        $zweistConfiguration = new ZweistConfiguration(
            [__DIR__ . '/InvalidController'],
            $this->zweistConfiguration->openApiJsonPath . '.invalid',
            $this->zweistConfiguration->routerJsonPath . '.invalid',
        );

        $zg = new ZweistOpenApiGenerator($zweistConfiguration, $this->container);

        /** @var InvalidArgumentException $invalidArgumentException */
        $invalidArgumentException = $this->assertExceptionThrown(
            InvalidArgumentException::class,
            $zg->generate(...)
        );

        $this->assertSame(
            sprintf(
                'Class "%s" parameter $itemType requires a scalar type or a schema. Got "invalid" instead.',
                PaginatedJsonResponse::class
            ),
            $invalidArgumentException->getMessage(),
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws JsonException
     */
    public function testScalarPagination(): void
    {
        $app = new App($this->psr17Factory);
        $zweistOpenApiGenerator = new ZweistOpenApiGenerator($this->zweistConfiguration, $this->container);
        $zweistOpenApiGenerator->generate();
        $zweistRouteService = new ZweistRouteService($this->zweistConfiguration, $this->container);
        $zweistRouteService->applyRoutes($app);

        $callScalarUrl = function (string $url) use ($app): string {
            $response = $app->handle(
                $this->psr17Factory->createServerRequest('GET', $url)
            );

            return (string) $response->getBody();
        };

        self::assertSame(
            json_encode(
                [
                    'pagination' => new PaginationData(1, 3, 11),
                    'data' => ['zero', 'one', 'two'],
                ],
                JSON_THROW_ON_ERROR
            ),
            $callScalarUrl('/api/page-scalar'),
        );

        self::assertSame(
            json_encode(
                [
                    'pagination' => new PaginationData(1, 3, 11),
                    'data' => ['zero', 'one', 'two'],
                ],
                JSON_THROW_ON_ERROR
            ),
            $callScalarUrl('/api/page-scalar?page=0'),
        );

        self::assertSame(
            json_encode(
                [
                    'pagination' => new PaginationData(1, 3, 11),
                    'data' => ['zero', 'one', 'two'],
                ],
                JSON_THROW_ON_ERROR
            ),
            $callScalarUrl('/api/page-scalar?page=1'),
        );

        self::assertSame(
            json_encode(
                [
                    'pagination' => new PaginationData(2, 3, 11),
                    'data' => ['three', 'four', 'five'],
                ],
                JSON_THROW_ON_ERROR
            ),
            $callScalarUrl('/api/page-scalar?page=2'),
        );

        self::assertSame(
            json_encode(
                [
                    'pagination' => new PaginationData(4, 3, 11),
                    'data' => ['nine', 'ten'],
                ],
                JSON_THROW_ON_ERROR
            ),
            $callScalarUrl('/api/page-scalar?page=20'),
        );
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws JsonException
     */
    public function testObjectPagination(): void
    {
        $app = new App($this->psr17Factory);
        $zweistOpenApiGenerator = new ZweistOpenApiGenerator($this->zweistConfiguration, $this->container);
        $zweistOpenApiGenerator->generate();
        $zweistRouteService = new ZweistRouteService($this->zweistConfiguration, $this->container);
        $zweistRouteService->applyRoutes($app);

        $callScalarUrl = function (string $url) use ($app): string {
            $response = $app->handle(
                $this->psr17Factory->createServerRequest('GET', $url)
            );

            return (string) $response->getBody();
        };

        self::assertSame(
            json_encode(
                [
                    'pagination' => new PaginationData(1, 3, 11),
                    'data' => [
                        new PaginatedObject('zero'),
                        new PaginatedObject('one'),
                        new PaginatedObject('two'),
                    ],
                ],
                JSON_THROW_ON_ERROR
            ),
            $callScalarUrl('/api/page-object'),
        );

        self::assertSame(
            json_encode(
                [
                    'pagination' => new PaginationData(1, 3, 11),
                    'data' => [
                        new PaginatedObject('zero'),
                        new PaginatedObject('one'),
                        new PaginatedObject('two'),
                    ],
                ],
                JSON_THROW_ON_ERROR
            ),
            $callScalarUrl('/api/page-object?page=0'),
        );

        self::assertSame(
            json_encode(
                [
                    'pagination' => new PaginationData(1, 3, 11),
                    'data' => [
                        new PaginatedObject('zero'),
                        new PaginatedObject('one'),
                        new PaginatedObject('two'),
                    ],
                ],
                JSON_THROW_ON_ERROR
            ),
            $callScalarUrl('/api/page-object?page=1'),
        );

        self::assertSame(
            json_encode(
                [
                    'pagination' => new PaginationData(2, 3, 11),
                    'data' => [
                        new PaginatedObject('three'),
                        new PaginatedObject('four'),
                        new PaginatedObject('five'),
                    ],
                ],
                JSON_THROW_ON_ERROR
            ),
            $callScalarUrl('/api/page-object?page=2'),
        );

        self::assertSame(
            json_encode(
                [
                    'pagination' => new PaginationData(4, 3, 11),
                    'data' => [
                        new PaginatedObject('nine'),
                        new PaginatedObject('ten'),
                    ],
                ],
                JSON_THROW_ON_ERROR
            ),
            $callScalarUrl('/api/page-object?page=20'),
        );
    }
}
