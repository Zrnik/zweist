<?php

/** @noinspection PhpUndefinedClassInspection */

declare(strict_types=1);

namespace Zrnik\Zweist\Tests\ExampleSchema;

use JsonSerializable;
use Nyholm\Psr7\Factory\Psr17Factory;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use Psr\Http\Message\StreamFactoryInterface;

#[Schema]
class SchemaWithWrongIntersection implements JsonSerializable
{
    public object $returnedValue;

    #[Property]
    public StreamFactoryInterface&NonExistentInterface $intersectionValue; // @phpstan-ignore-line

    public function __construct()
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
