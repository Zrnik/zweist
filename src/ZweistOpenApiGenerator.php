<?php

declare(strict_types=1);

namespace Zrnik\Zweist;

use JsonException;
use OpenApi\Annotations\OpenApi;
use OpenApi\Generator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Log\LoggerInterface;
use UnexpectedValueException;
use Zrnik\Zweist\System\OpenApiAnalyser;

class ZweistOpenApiGenerator
{
    public function __construct(
        private readonly ZweistConfiguration $zweistConfiguration,
        private readonly ContainerInterface $container,
    )
    {
    }

    /**
     * @return void
     * @throws JsonException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function generate(): void
    {
        $openApiAnalyser = new OpenApiAnalyser();

        $openApi = Generator::scan(
            $this->zweistConfiguration->openApiDefinitionPaths,
            [
                'analysis' => $openApiAnalyser,
                'logger' => $this->container->get(LoggerInterface::class),
            ],
        );

        if (! $openApi instanceof OpenApi) { //@codeCoverageIgnoreStart
            throw new UnexpectedValueException(
                'Unable to generate OpenAPI!'
            );
        } //@codeCoverageIgnoreEnd

        file_put_contents(
            $this->zweistConfiguration->openApiJsonPath,
            $openApi->toJson()
        );

        file_put_contents(
            $this->zweistConfiguration->routerJsonPath,
            $openApiAnalyser->toJson()
        );
    }
}
