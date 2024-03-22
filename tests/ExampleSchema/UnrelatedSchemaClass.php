<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests\ExampleSchema;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema]
class UnrelatedSchemaClass
{
    public function __construct(
        #[Property]
        public string $unrelatedValue = 'unrelated-text'
    )
    {
    }
}
