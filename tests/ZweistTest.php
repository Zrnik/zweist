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
use RuntimeException;
use Slim\App;
use Zrnik\Zweist\Exception\MisconfiguredOpenApiGeneratorException;
use Zrnik\Zweist\ZweistConfiguration;
use Zrnik\Zweist\ZweistOpenApiGenerator;
use Zrnik\Zweist\ZweistRouteService;

class ZweistTest extends TestCase
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

        $psr17Factory = new Psr17Factory();

        $app = new App($psr17Factory);

        $zweistRouteService = new ZweistRouteService(
            $this->zweistConfiguration,
            $this->container
        );
        $zweistRouteService->applyRoutes($app);

        foreach (['John', 'Doe'] as $name) {
            $response = $app->handle(
                $psr17Factory->createServerRequest(
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
                $psr17Factory->createServerRequest(
                    'POST',
                    '/api/goodbye'
                )->withBody(
                    $psr17Factory->createStream(
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
}
