<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Content\Exception;

use JsonException;
use Throwable;

class JsonResponseException extends JsonContentException
{
    /**
     * @param array<string, string> $contentErrors
     * @noinspection PhpSameParameterValueInspection
     */
    final private function __construct(
        string $message,
        array $contentErrors,
        ?Throwable $previous = null
    )
    {
        parent::__construct($message, $contentErrors, 500, $previous);
    }

    public static function fromJsonException(JsonException $jsonException): self
    {
        return new self($jsonException->getMessage(), [], $jsonException);
    }
}
