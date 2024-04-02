<?php

declare(strict_types=1);

namespace Zrnik\Zweist\Content\Exception;

use EventSauce\ObjectHydrator\UnableToHydrateObject;
use JsonException;
use Throwable;
use TypeError;

class JsonRequestException extends JsonContentException
{
    final private function __construct(
        string $message,
        array $contentErrors,
        ?Throwable $previous = null
    )
    {
        parent::__construct($message, $contentErrors, 400, $previous);
    }

    public static function fromUnableToHydrateException(UnableToHydrateObject $unableToHydrateObject): self
    {

        $errors = array_map(
            static fn(string $missingField) => sprintf('Field "%s" missing.', $missingField),
            $unableToHydrateObject->missingFields(),
        );

        return new self(
            $unableToHydrateObject->getMessage(),
            $errors,
            $unableToHydrateObject,
        );
    }

    public static function fromJsonTypeError(TypeError $typeError): self
    {
        // Parse schema name:
        [$schemaName] = explode('Argument #', $typeError->getMessage());
        $schemaName = str_replace('::__construct(): ', '', $schemaName);
        $schemaName = trim($schemaName);
        $schemaName = explode('\\', $schemaName);
        $schemaName = end($schemaName);
        $schemaName = trim($schemaName);
        assert(is_string($schemaName));

        // Parse type error message:
        [, $argumentInfo] = explode('__construct():', $typeError->getMessage());
        [$argumentInfo] = explode(', called in', $argumentInfo);
        $argumentInfo = trim($argumentInfo) . '.';

        return new self(
            sprintf('Unable to hydrate schema "%s" from JSON.', $schemaName),
            [$argumentInfo],
            $typeError,
        );
    }

    public static function fromJsonException(JsonException $jsonException): self
    {
        $supportedJsonErrors = [
            'Syntax error' => 'Value provided is not a valid JSON.',
        ];

        $supportedMessage = $supportedJsonErrors[$jsonException->getMessage()] ?? null;

        if ($supportedMessage !== null) {
            return new self($supportedMessage, [], $jsonException);
        }

        return new self('Unknown error', [], $jsonException); // @codeCoverageIgnore
    }

    /**
     * @param Throwable $throwable
     * @return self
     * @codeCoverageIgnore
     */
    public static function unhandledThrowable(Throwable $throwable): self
    {
        return new self(
            sprintf('Unhandled "%s" exception, see previous!', get_debug_type($throwable)),
            [],
            $throwable,
        );
    }

    /**
     * @param UnableToHydrateObject $throwable
     * @return self
     * @codeCoverageIgnore
     */
    public static function unhandledObjectHydrate(UnableToHydrateObject $throwable): self
    {
        return new self(
            sprintf('Unhandled "%s" exception, while hydrating!', get_debug_type($throwable)),
            [],
            $throwable,
        );
    }
}
