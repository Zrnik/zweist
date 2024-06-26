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
    public ?string $invalidSchemaKey = null;

    public function __construct(
        #[Property]
        public readonly string $text = 'example-text',
        #[Property(nullable: true)]
        public readonly ?string $nullableText = 'nullable-text',
        #[Property(nullable: true)]
        public readonly ?string $nullableTextIsNull = null,
        #[Property(type: 'string', nullable: true)]
        public readonly ?string $nullableWithForcedTypeTextIsNull = null,
        #[Property]
        public readonly RuntimeException|LogicException|MisconfiguredOpenApiGeneratorException $unionExceptionProperty = new RuntimeException(),
        #[Property(type: UnrelatedSchemaClass::class)]
        public readonly string $unrelatedSchema = 'unrelated-schema',
        #[Property(type: NotASchemaClass::class)]
        public readonly string $unrelatedNonSchemaClass = 'unrelated-not-schema',
        #[Property(type: NotASchemaClass::class)]
        public readonly NotASchemaClass $nonSchema = new NotASchemaClass(),
        #[Property(type: ['string', 'int'])]
        public readonly string $doubleForcedType = 'double-forced-type',
        #[Property(type: ['number'])]
        public readonly float $floatNumber = 0.33,
    )
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        $jsonData = [
            'text' => $this->text,
            'nullableText' => $this->nullableText,
            'nullableTextIsNull' => $this->nullableTextIsNull,
            'nullableWithForcedTypeTextIsNull' => $this->nullableWithForcedTypeTextIsNull,
            'unionExceptionProperty' => $this->unionExceptionProperty,
            'unrelatedSchema' => new UnrelatedSchemaClass(),
            'unrelatedNonSchemaClass' => new NotASchemaClass(),
            'nonSchema' => $this->nonSchema,
            'doubleForcedType' => $this->doubleForcedType,
            'floatNumber' => $this->floatNumber,
        ];

        $invalidReturnValues = [
            'unrelatedSchema' => $this->unrelatedSchema,
            'unrelatedNonSchemaClass' => $this->unrelatedNonSchemaClass,
        ];

        if ($this->invalidSchemaKey !== null && array_key_exists($this->invalidSchemaKey, $invalidReturnValues)) {
            $jsonData[$this->invalidSchemaKey] = $invalidReturnValues[$this->invalidSchemaKey];
        }

        return $jsonData;
    }
}
