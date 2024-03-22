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

    abstract protected static function assertTrue(mixed $condition, string $message = ''): void;

    /**
     * @throws JsonException
     */
    public function assertSchemaReturnsCorrectJson(object $schemaObject): void
    {
        $this->assertIsSchemaObject($schemaObject);

        if ($schemaObject instanceof JsonSerializable) {
            $jsonArray = $schemaObject->jsonSerialize();
        } else {
            $jsonString = json_encode($schemaObject, JSON_THROW_ON_ERROR);
            $jsonArray = json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR);
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
                if ($this->objectExists($type) && ($value instanceof $type)) {
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
                throw new ExpectationFailedException(sprintf(
                    'Schema class "%s" expected to jsonSerialize "%s" property as %s of ["%s"] types, but returned "%s".',
                    get_debug_type($schemaObject),
                    $reflectionProperty->getName(),
                    $mustBeAllAtOnce ? 'all' : 'one',
                    implode('", "', $types),
                    get_debug_type($value),
                ));
            }
        }
    }

    public function assertIsSchemaObject(object $schemaObject): void
    {
        $schemaAttributes = AttributeReflection::getClassAttributes(Schema::class, $schemaObject);

        if (count($schemaAttributes) > 0) {
            $this->addToAssertionCount(1);
            return; // Ok
        }

        throw new ExpectationFailedException(sprintf(
            'No schema attribute found on class "%s".',
            get_debug_type($schemaObject),
        ));
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
}
