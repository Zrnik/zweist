<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Tests\ExampleSchema\Helpers;

use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

class StreamFactory implements StreamFactoryInterface
{
    public function createStream(string $content = ''): StreamInterface
    {
        return (new Psr17Factory())->createStream($content);
    }

    public function createStreamFromFile(string $filename, string $mode = 'r'): StreamInterface
    {
        return (new Psr17Factory())->createStreamFromFile($filename, $mode);
    }

    public function createStreamFromResource($resource): StreamInterface
    {
        return (new Psr17Factory())->createStreamFromResource($resource);
    }
}