<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests\ExampleSchema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema]
class SchemaWithRecursiveProperty
{
    public function __construct(
        #[Property]
        public ?SchemaWithRecursiveProperty $recursiveProperty = null,
    )
    {
        $this->recursiveProperty = $this;
    }
}
