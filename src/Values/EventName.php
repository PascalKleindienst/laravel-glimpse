<?php

declare(strict_types=1);

namespace LaravelGlimpse\Values;

use LaravelGlimpse\Exceptions\InvalidEventNameException;
use Stringable;

/**
 * Typed, validated event name.
 *
 * Rules:
 *   - Not empty
 *   - Max 100 characters (matches DB column width)
 *   - Only `[a-zA-Z0-9._-]` - no spaces or special chars that could break
 *     dimension keys like "event:my.name"
 *
 * Usage:
 * ```
 *   $name = EventName::from('checkout_completed');
 *   (string) $name; // 'checkout_completed'
 * ```
 */
final readonly class EventName implements Stringable
{
    private const int MAX_LENGTH = 100;

    // Pattern matches letters, digits, underscores, hyphens, dots.
    private const string PATTERN = '/^[a-zA-Z0-9._\-]+$/';

    private function __construct(private string $value) {}

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * @throws InvalidEventNameException if name is invalid
     */
    public static function from(string $name): self
    {
        $name = mb_trim($name);

        if ($name === '') {
            throw InvalidEventNameException::empty();
        }

        if (mb_strlen($name) > self::MAX_LENGTH) {
            throw InvalidEventNameException::tooLong($name, self::MAX_LENGTH);
        }

        if (! preg_match(self::PATTERN, $name)) {
            throw InvalidEventNameException::invalidCharacters($name);
        }

        return new self($name);
    }

    /**
     * Like from(), but returns null instead of throwing for invalid names.
     * Useful when you want to silently discard bad event names in production.
     */
    public static function tryFrom(string $name): ?self
    {
        try {
            return self::from($name);
        } catch (InvalidEventNameException) {
            return null;
        }
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
