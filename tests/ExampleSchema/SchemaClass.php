<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests\ExampleSchema;

use JsonSerializable;
use LogicException;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use RuntimeException;
use Zrnik\Zweist\Exception\MisconfiguredOpenApiGeneratorException;

#[Schema]
class SchemaClass implements JsonSerializable
{
    public bool $returnOk = true;

    public function __construct(
        #[Property]
        public readonly string $text = 'example-text',
        #[Property(nullable: true)]
        public readonly ?string $nullableText = 'nullable-text',
        #[Property]
        public readonly RuntimeException|LogicException|MisconfiguredOpenApiGeneratorException $unionExceptionProperty = new RuntimeException(),
        #[Property(type: UnrelatedSchemaClass::class)]
        public readonly string $unrelatedSchema = 'unrelated-schema',
        #[Property(type: ['string', 'int'])]
        public readonly string $doubleForcedType = 'double-forced-type',
    )
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        if (! $this->returnOk) {
            return [
                'text' => $this->text,
                'nullableText' => $this->nullableText,
                'unionExceptionProperty' => $this->unionExceptionProperty,
                'unrelatedSchema' => $this->unrelatedSchema,
                'doubleForcedType' => $this->doubleForcedType,
            ];
        }

        return [
            'text' => $this->text,
            'nullableText' => $this->nullableText,
            'unionExceptionProperty' => $this->unionExceptionProperty,
            'unrelatedSchema' => new UnrelatedSchemaClass(),
            'doubleForcedType' => $this->doubleForcedType,
        ];
    }
}