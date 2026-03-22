<?php

declare(strict_types=1);

namespace LaravelGlimpse\Exceptions;

use Exception;

use function sprintf;

final class InvalidEventNameException extends Exception
{
    public static function empty(): self
    {
        return new self('Glimpse event name must not be empty.');
    }

    public static function tooLong(string $name, int $maxLength): self
    {
        return new self(sprintf(
            'Glimpse event name "%s" exceeds the maximum length of %d characters (%d given).',
            $name,
            $maxLength,
            mb_strlen($name),
        ));
    }

    public static function invalidCharacters(string $name): self
    {
        return new self(sprintf(
            'Glimpse event name "%s" contains invalid characters. '
            .'Only letters, numbers, underscores, hyphens and dots are allowed.',
            $name,
        ));
    }
}
