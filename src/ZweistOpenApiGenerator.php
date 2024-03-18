<?php

declare(strict_types=1);

namespace Zrnik\Zweist;

use JsonException;
use OpenApi\Annotations\OpenApi;
use OpenApi\Generator;
use UnexpectedValueException;
use Zrnik\Zweist\System\OpenApiAnalyser;

class ZweistOpenApiGenerator
{
    public function __construct(
        private readonly ZweistConfiguration $zweistConfiguration,
    )
    {
    }

    /**
     * @throws JsonException
     */
    public function generate(): void
    {
        $openApiAnalyser = new OpenApiAnalyser();

        $openApi = Generator::scan(
            $this->zweistConfiguration->openApiDefinitionPaths,
            [
                'analysis' => $openApiAnalyser,
                'logger' => $this->zweistConfiguration->logger,
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
