<?php

declare(strict_types=1);

namespace Zrnik\Zweist\System;

use JsonException;
use OpenApi\Analysis;
use OpenApi\Annotations\AbstractAnnotation;
use OpenApi\Annotations\Operation;
use OpenApi\Attributes\Middleware;
use OpenApi\Context;
use Zrnik\AttributeReflection\AttributeReflection;
use Zrnik\Zweist\ZweistConfiguration;
use Zrnik\Zweist\ZweistRouteService;

/**
 * @phpstan-import-type SlimRouteDataArrayShape from ZweistRouteService
 */
class OpenApiAnalyser extends Analysis
{
    /** @phpstan-var SlimRouteDataArrayShape[] */
    private array $routes = [];

    public function __construct(
        private readonly ZweistConfiguration $zweistConfiguration
    )
    {
        parent::__construct([], new Context());
    }

    public function addAnnotation(object $annotation, Context $context): void
    {
        /** @var class-string $class */
        $class = sprintf(
            '%s\%s',
            $context->namespace,
            $context->class
        );

        $method = (string) $context->method;

        if ($annotation instanceof Operation) {
            $middleware = [];

            /** @var Middleware $middlewareAttribute */
            foreach (
                AttributeReflection::getMethodAttributes(Middleware::class, $class, $method)
                as $middlewareAttribute
            ) {
                $middleware[] = $middlewareAttribute->middlewareClass;
            }

            /** @var Middleware $middlewareAttribute */
            foreach (
                AttributeReflection::getClassAttributes(Middleware::class, $class)
                as $middlewareAttribute
            ) {
                $middleware[] = $middlewareAttribute->middlewareClass;
            }

            $this->routes[] = [
                'http_method' => $annotation->method,
                'url' => $annotation->path,
                'controller_class' => $class,
                'controller_method' => $method,
                'middleware' => $middleware,
            ];
        }

        foreach ($this->zweistConfiguration->inspectors as $inspector) {
            if ($annotation instanceof AbstractAnnotation) {
                $inspector->inspect($class, $method, $annotation);
            }
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
