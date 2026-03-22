<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use LaravelGlimpse\Exceptions\InvalidEventNameException;

it('creates empty exception with correct message', function (): void {
    $exception = InvalidEventNameException::empty();

    expect($exception->getMessage())->toBe('Glimpse event name must not be empty.')
        ->and($exception)->toBeInstanceOf(Exception::class);
});

it('creates tooLong exception with correct message', function (): void {
    $name = Str::repeat('a', 50);
    $exception = InvalidEventNameException::tooLong($name, 100);

    expect($exception->getMessage())->toBe(sprintf(
        'Glimpse event name "%s" exceeds the maximum length of %d characters (%d given).',
        $name,
        100,
        50,
    ));
});

it('creates tooLong exception with actual length in message', function (): void {
    $name = 'verylongname';
    $exception = InvalidEventNameException::tooLong($name, 5);

    expect($exception->getMessage())->toBe(sprintf(
        'Glimpse event name "%s" exceeds the maximum length of %d characters (%d given).',
        $name,
        5,
        mb_strlen($name),
    ));
});

it('creates invalidCharacters exception with correct message', function (): void {
    $name = 'event@name';
    $exception = InvalidEventNameException::invalidCharacters($name);

    expect($exception->getMessage())->toBe(sprintf(
        'Glimpse event name "%s" contains invalid characters. Only letters, numbers, underscores, hyphens and dots are allowed.',
        $name,
    ));
});

it('invalidCharacters exception includes the invalid name', function (): void {
    $name = 'bad/event!name';
    $exception = InvalidEventNameException::invalidCharacters($name);

    expect($exception->getMessage())->toContain($name);
});

it('all factory methods return self', function (): void {
    expect(InvalidEventNameException::empty())->toBeInstanceOf(InvalidEventNameException::class)
        ->and(InvalidEventNameException::tooLong('test', 10))->toBeInstanceOf(InvalidEventNameException::class)
        ->and(InvalidEventNameException::invalidCharacters('test'))->toBeInstanceOf(InvalidEventNameException::class);
});
