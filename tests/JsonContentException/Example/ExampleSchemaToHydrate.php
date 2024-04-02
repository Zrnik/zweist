<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests\JsonContentException\Example;

class ExampleSchemaToHydrate
{
    public function __construct(
        public string $string,
        public int $int,
        public bool $bool,
        public ?string $nullable,
        public SubSchema $object,
        public string $default = 'default',
    )
    {
    }
}
