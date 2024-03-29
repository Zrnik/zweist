<?php

declare(strict_types=1);

namespace Zrnik\Zweist;

use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Server\MiddlewareInterface;
use RuntimeException;
use Slim\Routing\Route;
use Slim\Routing\RouteCollectorProxy;

/**
 * @phpstan-type SlimRouteDataArrayShape array{
 *     http_method: string,
 *     url: string,
 *     controller_class: string,
 *     controller_method: string,
 *     middleware: string[],
 * }
 */
class ZweistRouteService
{
    public function __construct(
        private readonly ZweistConfiguration $zweistConfiguration,
        private readonly ContainerInterface $container,
    )
    {
    }

    /**
     * @param RouteCollectorProxy $routeCollectorProxy
     * @return void
     * @throws JsonException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function applyRoutes(RouteCollectorProxy $routeCollectorProxy): void
    {
        $routerJson = @file_get_contents($this->zweistConfiguration->routerJsonPath);

        if ($routerJson === false) {
            throw new RuntimeException(
                sprintf(
                    'Unable to read "%s" file!',
                    $this->zweistConfiguration->routerJsonPath
                )
            );
        }

        /** @var SlimRouteDataArrayShape[] $routerData */
        $routerData = json_decode($routerJson, true, 512, JSON_THROW_ON_ERROR);

        foreach ($routerData as $routeSettings) {

            /** @var Route $route */
            $route = $routeCollectorProxy->{strtolower($routeSettings['http_method'])}(
                $routeSettings['url'],
                [$routeSettings['controller_class'], $routeSettings['controller_method']]
            );

            /** @var class-string<MiddlewareInterface> $middlewareClass */
            foreach ($routeSettings['middleware'] as $middlewareClass) {
                /** @var MiddlewareInterface $middleware */
                $middleware = $this->container->get($middlewareClass);

                $route->addMiddleware($middleware);
            }
        }
    }
}
