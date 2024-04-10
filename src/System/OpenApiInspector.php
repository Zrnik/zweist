<?php

declare(strict_types=1);

namespace Zrnik\Zweist\System;

use OpenApi\Annotations\AbstractAnnotation;

interface OpenApiInspector
{
    /**
     * @param class-string $className
     * @param string $methodName
     * @param AbstractAnnotation $annotation
     * @return void
     */
    public function inspect(string $className, string $methodName, AbstractAnnotation $annotation): void;
}
