<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Content\Exception;

use InvalidArgumentException;
use Throwable;

abstract class JsonContentException extends InvalidArgumentException
{
    /**
     * @param string $message
     * @param string[] $contentErrors
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        string $message,
        private readonly array $contentErrors,
        int $code = 500,
        ?Throwable $previous = null
    )
    {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string[]
     */
    public function getContentErrors(): array
    {
        return $this->contentErrors;
    }
}
