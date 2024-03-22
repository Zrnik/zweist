<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests\ExampleSchema;

use JsonSerializable;
use Nyholm\Psr7\Factory\Psr17Factory;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

#[Schema]
class SchemaWithIntersection implements JsonSerializable
{
    public object $returnedValue;

    public function __construct(
        #[Property]
        public readonly StreamFactoryInterface&RequestFactoryInterface $intersectionValue = new Psr17Factory(),
    )
    {
        $this->returnedValue = new Psr17Factory();
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return [
            'intersectionValue' => $this->returnedValue,
        ];
    }
}
