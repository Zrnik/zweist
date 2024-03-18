<?php

declare(strict_types=1);

namespace Zrnik\Zweist\System;

use JsonException;
use OpenApi\Analysis;
use OpenApi\Annotations\Operation;
use OpenApi\Attributes\Middleware;
use OpenApi\Context;
use Zrnik\AttributeReflection\AttributeReflection;
use Zrnik\Zweist\ZweistRouteService;

/**
 * @phpstan-import-type SlimRouteDataArrayShape from ZweistRouteService
 */
class OpenApiAnalyser extends Analysis
{
    /** @phpstan-var SlimRouteDataArrayShape[] */
    private array $routes = [];

    public function __construct()
    {
        parent::__construct([], new Context());
    }

    #[\Override]
    public function addAnnotation(object $annotation, Context $context): void
    {
        if ($annotation instanceof Operation) {

            /** @var class-string $controllerClass */
            $controllerClass = sprintf(
                '%s\%s',
                $context->namespace,
                $context->class
            );

            $method = (string) $context->method;

            $middleware = [];

            /** @var Middleware $middlewareAttribute */
            foreach (
                AttributeReflection::getMethodAttributes(Middleware::class, $controllerClass, $method)
                as $middlewareAttribute
            ) {
                $middleware[] = $middlewareAttribute->middlewareClass;
            }

            /** @var Middleware $middlewareAttribute */
            foreach (
                AttributeReflection::getClassAttributes(Middleware::class, $controllerClass)
                as $middlewareAttribute
            ) {
                $middleware[] = $middlewareAttribute->middlewareClass;
            }

            $this->routes[] = [
                'http_method' => $annotation->method,
                'url' => $annotation->path,
                'controller_class' => $controllerClass,
                'controller_method' => $method,
                'middleware' => $middleware,
            ];
        }

        parent::addAnnotation($annotation, $context);
    }

    /**
     * @throws JsonException
     */
    public function toJson(): string
    {
        return json_encode($this->routes, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
    }
}
