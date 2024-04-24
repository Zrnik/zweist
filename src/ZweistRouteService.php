<?php

declare(strict_types=1);

namespace Zrnik\Zweist;

use JsonException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionNamedType;
use ReflectionUnionType;
use RuntimeException;
use Slim\Routing\Route;
use Slim\Routing\RouteCollectorProxy;
use Zrnik\Zweist\Content\MiddlewareWithContextInterface;

/**
 * @phpstan-type SlimRouteDataArrayShape array{
 *     http_method: string,
 *     url: string,
 *     controller_class: string,
 *     controller_method: string,
 *     middleware: array{class: class-string<MiddlewareInterface>, context: mixed}
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
     * @throws ReflectionException
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

            /**
             * @var array{class: class-string<MiddlewareInterface>, context: mixed} $middlewareData
             */
            foreach ($routeSettings['middleware'] as $middlewareData) {

                $reflectionClass = new ReflectionClass($middlewareData['class']);
                $reflectionMethod = $reflectionClass->getConstructor();

                $middleware = null;

                if ($reflectionMethod !== null) {
                    $parameterValues = [];

                    foreach ($reflectionMethod->getParameters() as $parameter) {
                        $parameterType = $parameter->getType();
                        $parameterValue = null;

                        if ($parameterType instanceof ReflectionUnionType) {
                            foreach ($parameterType->getTypes() as $type) {
                                if (($type instanceof ReflectionNamedType) && $parameterValue === null) {
                                    try {
                                        $parameterValue = $this->container->get($type->getName());
                                    } catch (ContainerExceptionInterface) { // @codeCoverageIgnoreStart
                                        /*noop*/
                                    } // @codeCoverageIgnoreEnd
                                }
                            }
                        }

                        if (($parameterType instanceof ReflectionNamedType) && ($parameterValue === null)) {
                            try {
                                $parameterValue = $this->container->get($parameterType->getName());
                            } catch (ContainerExceptionInterface) { // @codeCoverageIgnoreStart
                                /*noop*/
                            } // @codeCoverageIgnoreEnd
                        }

                        $parameterValues[$parameter->getName()] = $parameterValue;
                    }

                    $middleware = new $middlewareData['class'](...$parameterValues);
                } else {
                    $middleware = new $middlewareData['class']();
                }

                if ($middleware instanceof MiddlewareWithContextInterface) {
                    $middleware->setContext($middlewareData['context']);
                }

                if ($middleware instanceof MiddlewareInterface) {
                    $route->addMiddleware($middleware);
                }
            }
        }
    }
}
