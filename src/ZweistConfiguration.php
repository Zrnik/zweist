<?php

declare(strict_types=1);

namespace Zrnik\Zweist;

use Zrnik\Zweist\Exception\MisconfiguredOpenApiGeneratorException;

class ZweistConfiguration
{
    /**
     * @var string[]
     */
    public readonly array $openApiDefinitionPaths;

    /**
     * @param string[] $openApiDefinitionPaths
     */
    public function __construct(
        array $openApiDefinitionPaths,
        public readonly string $openApiJsonPath,
        public readonly string $routerJsonPath,
    )
    {
        if (count($openApiDefinitionPaths) === 0) {
            throw MisconfiguredOpenApiGeneratorException::noDefinitionPaths();
        }

        // Add Pagination Schema Object
        $openApiDefinitionPaths[] = __DIR__ . '/Content/Pagination';

        $this->openApiDefinitionPaths = $openApiDefinitionPaths;
    }
}
