<?php

declare(strict_types=1);

namespace Zrnik\Zweist\PHPUnit;

use JsonException;
use JsonSerializable;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\Schema;
use OpenApi\Generator;
use PHPUnit\Framework\ExpectationFailedException;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use Zrnik\AttributeReflection\AttributeReflection;

trait OpenApiSchemaCheck
{
    abstract protected function addToAssertionCount(int $howMuch);

    public function assertSchemaReturnsCorrectJson(object $schemaObject, ?string $message = null): void
    {
        $this->assertIsSchemaObject($schemaObject, $message);

        try {
            if ($schemaObject instanceof JsonSerializable) {
                $jsonArray = $schemaObject->jsonSerialize();
            } else {
                $jsonString = json_encode($schemaObject, JSON_THROW_ON_ERROR);
                $jsonArray = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
            }
        } catch (JsonException $jsonException) {
            throw new ExpectationFailedException(
                $message
                ??
                sprintf(
                    'Schema class "%s" threw an exception while JSON serializing. Message: %s',
                    get_debug_type($schemaObject),
                    $jsonException->getMessage()
                ),
                null,
                $jsonException
            );
        }

        $reflectionClass = new ReflectionClass($schemaObject);
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {

            if (! $this->isSchemaProperty($schemaObject, $reflectionProperty)) {
                continue;
            }

            $value = $jsonArray[$reflectionProperty->getName()];

            $schemaAttribute = $this->getPropertyAttribute($schemaObject, $reflectionProperty);

            $forcedType = $schemaAttribute->type !== Generator::UNDEFINED ? $schemaAttribute->type : null;

            $types = [];
            $mustBeAllAtOnce = false;

            if ($forcedType !== null) {
                if (is_array($forcedType)) {
                    $forcedTypes = $forcedType;
                } else {
                    $forcedTypes = [$forcedType];
                }

                /** @var string|class-string $forcedType */
                foreach ($forcedTypes as $forcedType) {
                    if ($this->objectExists($forcedType) && ($value instanceof $forcedType) && $this->isSchemaClass($value)) {
                        $this->assertSchemaReturnsCorrectJson($value);
                        continue 2;
                    }

                    $types[] = $forcedType;
                }
            }

            if ($forcedType === null) {
                $reflectionTypeContainer = $reflectionProperty->getType();

                if ($reflectionTypeContainer instanceof ReflectionUnionType) {
                    /**
                     * @var ReflectionNamedType $reflectionNamedType
                     * @noinspection PhpRedundantVariableDocTypeInspection
                     * (phpstan thinks it is also ReflectionIntersectionType)
                     */
                    foreach ($reflectionTypeContainer->getTypes() as $reflectionNamedType) {
                        $types[] = $reflectionNamedType->getName();
                    }
                }

                if ($reflectionTypeContainer instanceof ReflectionNamedType) {
                    $types[] = $reflectionTypeContainer->getName();
                }

                if ($reflectionTypeContainer instanceof ReflectionIntersectionType) {
                    /** @var ReflectionNamedType $reflectionNamedType */
                    foreach ($reflectionTypeContainer->getTypes() as $reflectionNamedType) {
                        $types[] = $reflectionNamedType->getName();
                    }

                    $mustBeAllAtOnce = true;
                }
            }

            $found = false;

            foreach ($types as $type) {
                if (($value instanceof $type) && $this->objectExists($type)) {
                    $found = true;
                    continue;
                }

                if ($type === get_debug_type($value)) {
                    $found = true;
                    continue;
                }

                if ($mustBeAllAtOnce) {
                    $found = false;
                    break;
                }
            }

            if (! $found) {
                throw new ExpectationFailedException(
                    $message
                    ??
                    sprintf(
                        'Schema class "%s" expected to jsonSerialize "%s" property as %s of ["%s"] types, but returned "%s".',
                        get_debug_type($schemaObject),
                        $reflectionProperty->getName(),
                        $mustBeAllAtOnce ? 'all' : 'one',
                        implode('", "', $types),
                        get_debug_type($value),
                    )
                );
            }

            $this->addToAssertionCount(1);
        }
    }

    private function assertIsNotSchemaObject(object $noSchemaObject, ?string $message = null): void
    {
        $this->addToAssertionCount(1);

        if ($this->isSchemaClass($noSchemaObject)) {
            throw new ExpectationFailedException(
                $message
                ??
                sprintf(
                    'Expected non-schema class, but got "%s".',
                    get_debug_type($noSchemaObject),
                )
            );
        }
    }

    public function assertIsSchemaObject(object $schemaObject, ?string $message = null): void
    {
        $schemaAttributes = AttributeReflection::getClassAttributes(Schema::class, $schemaObject);
        $this->addToAssertionCount(1);

        if (count($schemaAttributes) > 0) {
            return; // Ok
        }

        throw new ExpectationFailedException(
            $message
            ??
            sprintf(
                'No schema attribute found on class "%s".',
                get_debug_type($schemaObject),
            )
        );
    }

    public function getPropertyAttribute(object $schemaObject, ReflectionProperty $reflectionProperty): Property
    {
        $propertyAttributes = AttributeReflection::getPropertyAttributes(
            Property::class,
            $schemaObject,
            $reflectionProperty->getName(),
        );

        return $propertyAttributes[0] ?? throw new ExpectationFailedException(sprintf(
            'Property attribute not found on class "%s" property "%s".',
            get_debug_type($schemaObject),
            $reflectionProperty->getName(),
        ));
    }

    private function isSchemaProperty(object $schemaObject, ReflectionProperty $reflectionProperty): bool
    {
        $propertyAttributes = AttributeReflection::getPropertyAttributes(
            Property::class,
            $schemaObject,
            $reflectionProperty->getName()
        );

        return count($propertyAttributes) !== 0;
    }

    private function isSchemaClass(object $schemaObject): bool
    {
        $classAttributes = AttributeReflection::getClassAttributes(
            Schema::class,
            $schemaObject,
        );

        return count($classAttributes) !== 0;
    }

    public function objectExists(string $className): bool
    {
        return class_exists($className)
            || interface_exists($className)
            || trait_exists($className)
            || enum_exists($className);
    }
}
