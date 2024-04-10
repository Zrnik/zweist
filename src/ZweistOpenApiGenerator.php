<?php

declare(strict_types=1);

namespace Zrnik\Zweist;

use OpenApi\Analysis;
use OpenApi\Annotations\OpenApi;
use OpenApi\Annotations\Operation;
use OpenApi\Attributes\Middleware;
use OpenApi\Context;
use OpenApi\Generator;
use OpenApi\Processors\ProcessorInterface;
use Psr\Log\LoggerInterface;
use Zrnik\AttributeReflection\AttributeReflection;

class ZweistOpenApiGenerator extends Generator implements ProcessorInterface
{
    public function __construct(
        private readonly ZweistConfiguration $zweistConfiguration,
        ?LoggerInterface $logger = null,
    )
    {
        parent::__construct($logger);
        $this->setVersion(OpenApi::VERSION_3_0_0);
        $this->addProcessor($this);
    }

    // @phpstan-ignore-next-line
    public function generate(?iterable $sources = null, ?Analysis $analysis = null, bool $validate = true): OpenApi
    {
        if ($sources === null) {
            $sources = $this->zweistConfiguration->openApiDefinitionPaths;
        }

        $openApi = parent::generate($sources);

        assert($openApi instanceof OpenApi);

        file_put_contents(
            $this->zweistConfiguration->openApiJsonPath,
            $openApi->toJson()
        );

        return $openApi;
    }

    /**
     * @throws \JsonException
     */
    public function __invoke(Analysis $analysis): void
    {

        $routes = [];

        foreach ($analysis->annotations as $annotation) {

            if ($annotation instanceof Operation) {
                assert($annotation->_context instanceof Context);

                /** @var class-string $class */
                $class = sprintf('%s\%s', $annotation->_context->namespace, $annotation->_context->class);

                $method = (string) $annotation->_context->method;

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

                $routes[] = [
                    'http_method' => $annotation->method,
                    'url' => $annotation->path,
                    'controller_class' => $class,
                    'controller_method' => $method,
                    'middleware' => $middleware,
                ];
            }
        }

        file_put_contents(
            $this->zweistConfiguration->routerJsonPath,
            json_encode($routes, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
        );
    }
}
