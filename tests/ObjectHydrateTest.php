<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests;

use Nyholm\Psr7\Factory\Psr17Factory;
use PHPUnit\Framework\TestCase;
use Zrnik\Zweist\Content\JsonContentFacade;
use Zrnik\Zweist\Tests\ExampleSchema\UnrelatedSchemaClass;

class ObjectHydrateTest extends TestCase
{
    private JsonContentFacade $jsonContentFacade;

    protected function setUp(): void
    {
        $this->jsonContentFacade = new JsonContentFacade(
            new Psr17Factory(),
            new Psr17Factory(),
        );
    }

    /**
     * This is tested, because object mapper by default
     * converts from camel case to snake case.
     *
     * We don't want that.
     *
     * @return void
     */
    public function testHydrate(): void
    {
        $json = [
            'unrelatedValue' => 'Example Text',
        ];

        /** @var UnrelatedSchemaClass $unrelatedSchemaClass */
        $unrelatedSchemaClass = $this->jsonContentFacade->hydrateObject(
            UnrelatedSchemaClass::class,
            $json
        );

        $this->assertSame('Example Text', $unrelatedSchemaClass->unrelatedValue);
    }
}
