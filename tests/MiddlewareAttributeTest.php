<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests;

use DI\Container;
use JsonException;
use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Slim\App;
use Zrnik\Zweist\Tests\ExampleApplication\ExampleMiddleware;
use Zrnik\Zweist\ZweistConfiguration;
use Zrnik\Zweist\ZweistOpenApiGenerator;
use Zrnik\Zweist\ZweistRouteService;

class MiddlewareAttributeTest extends TestCase
{
    private ZweistConfiguration $zweistConfiguration;

    private ContainerInterface $container;

    protected function setUp(): void
    {
        $this->container = new Container();
        $this->zweistConfiguration = new ZweistConfiguration(
            [__DIR__ . '/ExampleApplication'],
            __DIR__ . '/../temp/OpenApi.json',
            __DIR__ . '/../temp/router.json',
            $this->container,
        );
    }

    /**
     * @throws JsonException
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

        $zweistOpenApiGenerator = new ZweistOpenApiGenerator($this->zweistConfiguration);

        $zweistOpenApiGenerator->generate();

        self::assertFileExists($this->zweistConfiguration->openApiJsonPath);
        self::assertFileExists($this->zweistConfiguration->routerJsonPath);

        $psr17Factory = new Psr17Factory();

        $app = new App($psr17Factory);

        $zweistRouteService = new ZweistRouteService($this->zweistConfiguration);
        $zweistRouteService->applyRoutes($app);

        $response = $app->handle(
            $psr17Factory->createServerRequest(
                'GET',
                sprintf('/api/hello/world')
            )
        );

        $headerValues = $response->getHeader(ExampleMiddleware::HEADER_NAME);

        self::assertContains(ExampleMiddleware::HEADER_VALUE, $headerValues);
    }
}
