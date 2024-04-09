<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Content\Pagination;

use Attribute;
use InvalidArgumentException;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\MediaType;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Response;
use OpenApi\Attributes\Schema;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
class PaginatedJsonResponse extends Response
{
    public function __construct(
        int $response,
        string $itemType,
        ?string $description = null,
    )
    {
        $items = null;

        if (
            in_array(
                strtolower($itemType),
                [
                    'bool', 'boolean',
                    'int', 'integer',
                    'double', 'float',
                    'string',
                ]
            )
        ) {
            $itemType = strtolower($itemType);

            $openApiAlias = [
                'int' => 'integer',
                'integer' => 'integer',
                'double' => 'number',
                'float' => 'number',
                'bool' => 'boolean',
            ];

            $openApiType = $openApiAlias[$itemType] ?? $itemType;

            $items = new Items(type: $openApiType);
        }

        if (class_exists($itemType) || interface_exists($itemType) || enum_exists($itemType)) {
            $items = new Items(ref: $itemType);
        }

        if ($items === null) {
            throw new InvalidArgumentException(
                sprintf(
                    'Class "%s" parameter $itemType requires a scalar type or a schema. Got "%s" instead.',
                    self::class,
                    $itemType
                )
            );
        }

        parent::__construct(
            response: $response,
            description: $description,
            content: new MediaType(
                mediaType: 'application/json',
                schema: new Schema(
                    properties: [
                        new Property(
                            property: 'pagination',
                            ref: PaginationData::class
                        ),
                        new Property(
                            property: 'data',
                            type: 'array',
                            items: $items,
                        ),
                    ],
                    type: 'object'
                )
            ),
        );
    }
}
