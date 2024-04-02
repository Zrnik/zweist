<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests\JsonContentException\Example;

class SubSchema
{
    public function __construct(
        public string $string,
        public int $int,
        public bool $bool,
        public ?string $nullable,
    )
    {
    }
}
