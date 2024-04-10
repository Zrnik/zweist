<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests;

use DI\Container;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Zrnik\PHPUnit\Exceptions;
use Zrnik\Zweist\Tests\ExampleApplication\ExampleInspector;
use Zrnik\Zweist\ZweistConfiguration;
use Zrnik\Zweist\ZweistOpenApiGenerator;

class InspectorTest extends TestCase
{
    use Exceptions;

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
            [
                __DIR__ . '/ExampleApplication',
            ],
            __DIR__ . '/../temp/OpenApi.json',
            __DIR__ . '/../temp/router.json',
            [
                new ExampleInspector(),
            ],
        );
    }

    public function testInspectorCalled(): void
    {
        $zweistOpenApiGenerator = new ZweistOpenApiGenerator($this->zweistConfiguration, $this->container);

        /** @var Exception $exception */
        $exception = $this->assertExceptionThrown(
            Exception::class,
            fn() => $zweistOpenApiGenerator->generate(),
        );

        $this->assertSame(sprintf('%s called', ExampleInspector::class), $exception->getMessage());
    }
}
