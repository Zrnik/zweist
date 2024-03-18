<?php

declare(strict_types=1);

namespace Zrnik\Zweist;

use Psr\Log\LoggerInterface;
use Zrnik\Zweist\Exception\MisconfiguredOpenApiGeneratorException;

class ZweistConfiguration
{
    /**
     * @param string[] $openApiDefinitionPaths
     */
    public function __construct(
        public readonly array $openApiDefinitionPaths,
        public readonly string $openApiJsonPath,
        public readonly string $routerJsonPath,
        public readonly ?LoggerInterface $logger = null,
    )
    {
        if (count($openApiDefinitionPaths) === 0) {
            throw MisconfiguredOpenApiGeneratorException::noDefinitionPaths();
        }
    }
}
