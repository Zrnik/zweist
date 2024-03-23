<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests;

use JsonException;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use Zrnik\PHPUnit\Exceptions;
use Zrnik\Zweist\PHPUnit\OpenApiSchemaCheck;
use Zrnik\Zweist\Tests\ExampleSchema\Helpers\StreamFactory;
use Zrnik\Zweist\Tests\ExampleSchema\NotASchemaClass;
use Zrnik\Zweist\Tests\ExampleSchema\SchemaClass;
use Zrnik\Zweist\Tests\ExampleSchema\SchemaWithIntersection;
use Zrnik\Zweist\Tests\ExampleSchema\SchemaWithRecursiveProperty;
use Zrnik\Zweist\Tests\ExampleSchema\SchemaWithWrongIntersection;
use Zrnik\Zweist\Tests\ExampleSchema\UnrelatedSchemaClass;

class SchemaTest extends TestCase
{
    use Exceptions;
    use OpenApiSchemaCheck;

    public function testNotSchema(): void
    {
        $this->assertExceptionThrown(
            ExpectationFailedException::class,
            fn() => $this->assertIsSchemaObject(new NotASchemaClass())
        );
    }

    public function testSchemaCheck(): void
    {
        $this->assertIsSchemaObject(new SchemaClass());
        $this->assertIsSchemaObject(new UnrelatedSchemaClass());

        $this->assertSchemaReturnsCorrectJson(new SchemaClass());
        $this->assertSchemaReturnsCorrectJson(new UnrelatedSchemaClass());

        $failingSchema = new SchemaClass();
        $failingSchema->returnOk = false;

        $this->assertExceptionThrown(
            ExpectationFailedException::class,
            fn() => $this->assertSchemaReturnsCorrectJson($failingSchema)
        );

        $schemaWithIntersection = new SchemaWithIntersection();
        $this->assertSchemaReturnsCorrectJson($schemaWithIntersection);

        $schemaWithIntersection->returnedValue = new StreamFactory();
        $this->assertExceptionThrown(
            ExpectationFailedException::class,
            fn() => $this->assertSchemaReturnsCorrectJson($failingSchema)
        );

        $this->assertExceptionThrown(
            ExpectationFailedException::class,
            fn() => $this->assertSchemaReturnsCorrectJson(new SchemaWithWrongIntersection())
        );
    }

    public function testRecursivePropertyFails(): void
    {
        $expectationFailedException = $this->assertExceptionThrown(
            ExpectationFailedException::class,
            fn() => $this->assertSchemaReturnsCorrectJson(new SchemaWithRecursiveProperty())
        );

        self::assertInstanceOf(
            JsonException::class,
            $expectationFailedException->getPrevious(),
        );
    }

    public function testMessageReturned(): void
    {
        $expectationFailedException = $this->assertExceptionThrown(
            ExpectationFailedException::class,
            fn() => $this->assertSchemaReturnsCorrectJson(new SchemaWithRecursiveProperty(), 'test-message')
        );

        self::assertSame(
            'test-message',
            $expectationFailedException->getMessage(),
        );

        $expectationFailedException = $this->assertExceptionThrown(
            ExpectationFailedException::class,
            fn() => $this->assertIsSchemaObject(new NotASchemaClass(), 'test-message')
        );

        self::assertSame(
            'test-message',
            $expectationFailedException->getMessage(),
        );
    }
}
