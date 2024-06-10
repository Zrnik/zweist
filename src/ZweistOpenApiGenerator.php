<?php

declare(strict_types=1);

namespace Zrnik\Zweist;

use JsonException;
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
        Generator::$context ??= new Context();
    }

    // @phpstan-ignore-next-line
    public function generate(?iterable $sources = null, ?Analysis $analysis = null, bool $validate = true): OpenApi
    {
        if ($sources === null) {
            $sources = $this->zweistConfiguration->openApiDefinitionPaths;
        }

        $openApi = parent::generate($sources);

        assert($openApi instanceof OpenApi);

        foreach ($openApi->paths as $idx => $path) {
            $stringPath = str_replace('\\', '/', $path->path);
            $pathParts = explode('/', $stringPath);

            foreach ($pathParts as $pathPartIndex => $pathPart) {
                if (
                    str_starts_with($pathPart, '{')
                    && str_ends_with($pathPart, '}')
                    && str_contains($pathPart, ':')
                ) {
                    $pathParts[$pathPartIndex] = explode(':', $pathPart)[0] . '}';
                }
            }

            $openApi->paths[$idx]->path = implode('/', $pathParts);
        }

        file_put_contents(
            $this->zweistConfiguration->openApiJsonPath,
            $openApi->toJson()
        );

        return $openApi;
    }

    /**
     * @throws JsonException
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

                $middlewareData = [];

                /** @var Middleware $middlewareAttribute */
                foreach (
                    AttributeReflection::getMethodAttributes(Middleware::class, $class, $method)
                    as $middlewareAttribute
                ) {
                    $middlewareData[] = [
                        'class' => $middlewareAttribute->middlewareClass,
                        'context' => $middlewareAttribute->context,
                    ];
                }

                /** @var Middleware $middlewareAttribute */
                foreach (
                    AttributeReflection::getClassAttributes(Middleware::class, $class)
                    as $middlewareAttribute
                ) {
                    $middlewareData[] = [
                        'class' => $middlewareAttribute->middlewareClass,
                        'context' => $middlewareAttribute->context,
                    ];
                }

                $routes[] = [
                    'http_method' => $annotation->method,
                    'url' => $annotation->path,
                    'controller_class' => $class,
                    'controller_method' => $method,
                    'middleware' => $middlewareData,
                ];
            }
        }

        file_put_contents(
            $this->zweistConfiguration->routerJsonPath,
            json_encode($routes, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT)
        );
    }
}
