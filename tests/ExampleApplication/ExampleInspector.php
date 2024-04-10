<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests\ExampleApplication;

use Exception;
use OpenApi\Annotations\AbstractAnnotation;
use Zrnik\Zweist\System\OpenApiInspector;

class ExampleInspector implements OpenApiInspector
{
    /**
     * @throws Exception
     */
    public function inspect(string $className, string $methodName, AbstractAnnotation $annotation): void
    {
        /**
         * @noinspection ThrowRawExceptionInspection
         */
        throw new Exception(sprintf('%s called', self::class));
    }
}
