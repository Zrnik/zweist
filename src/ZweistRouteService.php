<?php

declare(strict_types=1);

namespace Zrnik\Zweist;

use JsonException;
use RuntimeException;
use Slim\Routing\RouteCollectorProxy;

/**
 * @phpstan-type SlimRouteDataArrayShape array{
 *     http_method: string,
 *     url: string,
 *     controller_class: string,
 *     controller_method: string
 * }
 */
class ZweistRouteService
{
    public function __construct(
        private readonly ZweistConfiguration $zweistConfiguration
    )
    {
    }

    /**
     * @throws JsonException
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

        foreach ($routerData as $route) {
            $routeCollectorProxy->{strtolower($route['http_method'])}(
                $route['url'],
                [$route['controller_class'], $route['controller_method']]
            );
        }
    }
}
