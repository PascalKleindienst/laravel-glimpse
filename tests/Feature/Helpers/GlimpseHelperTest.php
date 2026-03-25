<?php

declare(strict_types=1);

use LaravelGlimpse\Exceptions\InvalidEventNameException;
use LaravelGlimpse\Glimpse;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

it('returns Glimpse instance when called with no arguments', function (): void {
    $result = glimpse();

    expect($result)->toBeInstanceOf(Glimpse::class);
});

it('dispatches event when called with event name only', function (): void {
    glimpse('helper_event');

    assertDatabaseHas('glimpse_events', [
        'name' => 'helper_event',
        'properties' => null,
    ]);
});

it('dispatches event with payload when called with name and payload', function (): void {
    glimpse('helper_event_with_payload', ['key' => 'value', 'count' => 42]);

    assertDatabaseHas('glimpse_events', [
        'name' => 'helper_event_with_payload',
        'properties' => json_encode(['key' => 'value', 'count' => 42], JSON_THROW_ON_ERROR),
    ]);
});

it('dispatches multiple events', function (): void {
    glimpse('event_1', ['step' => 1]);
    glimpse('event_2', ['step' => 2]);
    glimpse('event_3', ['step' => 3]);

    assertDatabaseCount('glimpse_events', 3);
});

it('returns null when called with event name', function (): void {
    $result = glimpse('return_null_event');

    expect($result)->toBeNull();
});

it('returns Glimpse instance when called with null', function (): void {
    $result = glimpse();

    expect($result)->toBeInstanceOf(Glimpse::class);
});

it('throws exception for invalid event name', function (): void {
    glimpse('invalid name!');
})->throws(InvalidEventNameException::class);

it('allows chained calls when returning instance', function (): void {
    glimpse()->event('chained_event', ['via' => 'helper']);

    assertDatabaseHas('glimpse_events', [
        'name' => 'chained_event',
        'properties' => json_encode(['via' => 'helper'], JSON_THROW_ON_ERROR),
    ]);
});

it('handles empty payload array', function (): void {
    glimpse('empty_payload_event', []);

    assertDatabaseHas('glimpse_events', [
        'name' => 'empty_payload_event',
        'properties' => null,
    ]);
});
