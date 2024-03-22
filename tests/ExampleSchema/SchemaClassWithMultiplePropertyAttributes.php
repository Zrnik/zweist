<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests\ExampleSchema;

use JsonSerializable;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;

#[Schema]
class SchemaClassWithMultiplePropertyAttributes implements JsonSerializable
{
    public bool $returnOk = true;

    public function __construct(
        #[Property]
        public readonly string $text = 'example-text',
        #[Property(nullable: true)]
        public readonly ?string $nullableText = 'nullable-text',
        #[Property(type: UnrelatedSchemaClass::class)]
        public readonly string $unrelatedSchema = 'unrelated-schema',
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
                'unrelatedSchema' => $this->unrelatedSchema,
            ];
        }

        return [
            'text' => $this->text,
            'unrelatedSchema' => new UnrelatedSchemaClass(),
        ];
    }
}