<?php

declare(strict_types=1);

use Illuminate\Support\Str;
use LaravelGlimpse\Exceptions\InvalidEventNameException;
use LaravelGlimpse\Values\EventName;

it('creates event name from valid string', function (string $name): void {
    $eventName = EventName::from($name);

    expect((string) $eventName)->toBe($name)
        ->and($eventName->equals(EventName::from($name)))->toBeTrue();
})->with([
    'simple' => ['checkout'],
    'with_underscore' => ['page_view'],
    'with_hyphen' => ['user-signup'],
    'with_dot' => ['checkout.completed'],
    'mixed' => ['page.View_123'],
    'numbers_only' => ['12345'],
]);

it('trims whitespace from event name', function (): void {
    $eventName = EventName::from('  checkout  ');

    expect((string) $eventName)->toBe('checkout');
});

it('throws empty exception for empty string', function (): void {
    EventName::from('');
})->throws(InvalidEventNameException::class, 'Glimpse event name must not be empty.');

it('throws empty exception for whitespace only', function (): void {
    EventName::from('   ');
})->throws(InvalidEventNameException::class, 'Glimpse event name must not be empty.');

it('throws too long exception when exceeding max length', function (): void {
    EventName::from(Str::repeat('a', 101));
})->throws(InvalidEventNameException::class, 'exceeds the maximum length of 100 characters (101 given).');

it('accepts event name at exact max length', function (): void {
    $eventName = EventName::from(Str::repeat('a', 100));

    expect(mb_strlen((string) $eventName))->toBe(100);
});

it('throws invalid characters exception for special chars', function (string $name): void {
    EventName::from($name);
})->throws(InvalidEventNameException::class)->with([
    'spaces' => ['event name'],
    'at_sign' => ['event@name'],
    'hash' => ['event#name'],
    'dollar' => ['event$name'],
    'exclamation' => ['event!name'],
    'question' => ['event?name'],
    'slash' => ['event/name'],
    'backslash' => ['event\\name'],
    'parentheses' => ['event(name)'],
    'brackets' => ['event[name]'],
    'braces' => ['event{name}'],
    'pipe' => ['event|name'],
    'quote' => ['event"name'],
    'apostrophe' => ["event'name"],
    'plus' => ['event+name'],
    'equals' => ['event=name'],
    'less_than' => ['event<name'],
    'greater_than' => ['event>name'],
    'caret' => ['event^name'],
    'percent' => ['event%name'],
    'ampersand' => ['event&name'],
    'asterisk' => ['event*name'],
    'tilde' => ['event~name'],
    'backtick' => ['event`name'],
]);

it('tryFrom returns null for invalid names', function (string $name): void {
    expect(EventName::tryFrom($name))->toBeNull();
})->with([
    'empty' => [''],
    'whitespace' => ['   '],
    'too_long' => [Str::repeat('a', 101)],
    'with_space' => ['event name'],
    'with_at' => ['event@name'],
]);

it('tryFrom returns instance for valid names', function (string $name): void {
    $eventName = EventName::tryFrom($name);

    expect($eventName)->toBeInstanceOf(EventName::class)
        ->and((string) $eventName)->toBe($name);
})->with([
    'simple' => ['checkout'],
    'with_underscore' => ['page_view'],
    'with_hyphen' => ['user-signup'],
    'with_dot' => ['checkout.completed'],
]);

it('equals returns true for identical event names', function (): void {
    $eventName1 = EventName::from('checkout_completed');
    $eventName2 = EventName::from('checkout_completed');

    expect($eventName1->equals($eventName2))->toBeTrue();
});

it('equals returns false for different event names', function (): void {
    $eventName1 = EventName::from('checkout');
    $eventName2 = EventName::from('signup');

    expect($eventName1->equals($eventName2))->toBeFalse();
});

it('can be cast to string', function (): void {
    $eventName = EventName::from('checkout_completed');

    expect((string) $eventName)->toBe('checkout_completed');
});
