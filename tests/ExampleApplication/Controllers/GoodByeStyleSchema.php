<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests\ExampleApplication\Controllers;

use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema]
class GoodByeStyleSchema
{
    public function __construct(
        #[Property]
        public string $text,
    )
    {
    }
}