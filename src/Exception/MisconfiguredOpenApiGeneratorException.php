<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Exception;

use RuntimeException;
use Zrnik\Zweist\ZweistConfiguration;

class MisconfiguredOpenApiGeneratorException extends RuntimeException
{
    final private function __construct(string $message)
    {
        parent::__construct($message);
    }

    public static function noDefinitionPaths(): self
    {
        return new self(
            sprintf(
                'There are no paths defined in "%s".',
                ZweistConfiguration::class,
            )
        );
    }
}
