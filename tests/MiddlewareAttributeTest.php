<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests;

use DI\Container;
use JsonException;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use Slim\App;
use Zrnik\Zweist\Tests\ExampleApplication\ExampleMiddleware;
use Zrnik\Zweist\ZweistConfiguration;
use Zrnik\Zweist\ZweistOpenApiGenerator;
use Zrnik\Zweist\ZweistRouteService;

class MiddlewareAttributeTest extends TestCase
{
    private Container $container;

    private ZweistConfiguration $zweistConfiguration;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->container = new Container();

        $this->container->set(LoggerInterface::class, $this->createMock(LoggerInterface::class));

        $this->zweistConfiguration = new ZweistConfiguration(
            [__DIR__ . '/ExampleApplication'],
            __DIR__ . '/../temp/OpenApi.json',
            __DIR__ . '/../temp/router.json',
        );
    }

    /**
     * @return void
     * @throws JsonException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testMiddlewareCalled(): void
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

        $psr17Factory = new Psr17Factory();

        $app = new App($psr17Factory);

        $zweistRouteService = new ZweistRouteService($this->zweistConfiguration, $this->container);
        $zweistRouteService->applyRoutes($app);

        $response = $app->handle(
            $psr17Factory->createServerRequest(
                'GET',
                '/api/hello/world'
            )
        );

        $headerValues = $response->getHeader(ExampleMiddleware::HEADER_NAME);

        self::assertContains(ExampleMiddleware::HEADER_VALUE, $headerValues);
    }
}
