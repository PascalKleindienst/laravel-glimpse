<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use LaravelGlimpse\Events\GlimpseEventDispatchedEvent;
use LaravelGlimpse\Exceptions\InvalidEventNameException;
use LaravelGlimpse\Services\EventDispatcher;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

it('does not dispatch event when glimpse is disabled', function (): void {
    config(['glimpse.enabled' => false]);

    $dispatcher = resolve(EventDispatcher::class);
    $dispatcher->dispatch('test_event', ['key' => 'value']);

    assertDatabaseCount('glimpse_events', 0);
});

it('creates event with name and properties', function (): void {
    $dispatcher = resolve(EventDispatcher::class);
    $dispatcher->dispatch('checkout_completed', ['plan' => 'pro', 'value' => 99]);

    assertDatabaseHas('glimpse_events', [
        'name' => 'checkout_completed',
        'properties' => json_encode(['plan' => 'pro', 'value' => 99], JSON_THROW_ON_ERROR),
    ]);
});

it('creates event with empty properties', function (): void {
    $dispatcher = resolve(EventDispatcher::class);
    $dispatcher->dispatch('page_viewed');

    assertDatabaseHas('glimpse_events', [
        'name' => 'page_viewed',
        'properties' => null,
    ]);
});

it('creates event with custom session hash', function (): void {
    $dispatcher = resolve(EventDispatcher::class);
    $dispatcher->dispatch('custom_event', [], 'custom-session-hash');

    assertDatabaseHas('glimpse_events', [
        'session_hash' => 'custom-session-hash',
    ]);
});

it('creates multiple events', function (): void {
    $dispatcher = resolve(EventDispatcher::class);

    $dispatcher->dispatch('event_1', ['step' => 1]);
    $dispatcher->dispatch('event_2', ['step' => 2]);
    $dispatcher->dispatch('event_3', ['step' => 3]);

    assertDatabaseCount('glimpse_events', 3);
    assertDatabaseHas('glimpse_events', ['name' => 'event_1', 'properties' => json_encode(['step' => 1], JSON_THROW_ON_ERROR)]);
    assertDatabaseHas('glimpse_events', ['name' => 'event_2', 'properties' => json_encode(['step' => 2], JSON_THROW_ON_ERROR)]);
    assertDatabaseHas('glimpse_events', ['name' => 'event_3', 'properties' => json_encode(['step' => 3], JSON_THROW_ON_ERROR)]);
});

it('stores nested properties correctly', function (): void {
    $dispatcher = resolve(EventDispatcher::class);
    $dispatcher->dispatch('complex_event', [
        'user' => ['id' => 123, 'name' => 'John'],
        'metadata' => ['source' => 'api', 'version' => 1],
    ]);

    assertDatabaseHas('glimpse_events', [
        'name' => 'complex_event',
        'properties' => json_encode([
            'user' => ['id' => 123, 'name' => 'John'],
            'metadata' => ['source' => 'api', 'version' => 1],
        ], JSON_THROW_ON_ERROR),
    ]);
});

it('throws exception for invalid event name', function (): void {
    $dispatcher = resolve(EventDispatcher::class);
    $dispatcher->dispatch('invalid name!');
})->throws(InvalidEventNameException::class);

it('throws exception for empty event name', function (): void {
    $dispatcher = resolve(EventDispatcher::class);
    $dispatcher->dispatch('');
})->throws(InvalidEventNameException::class);

it('throws exception for event name exceeding max length', function (): void {
    $dispatcher = resolve(EventDispatcher::class);
    $dispatcher->dispatch(str_repeat('a', 101));
})->throws(InvalidEventNameException::class);

it('dispatchSilently does not throw for invalid event name', function (): void {
    $dispatcher = resolve(EventDispatcher::class);
    $dispatcher->dispatchSilently('invalid name!');
})->throwsNoExceptions();

it('dispatchSilently does not throw for empty event name', function (): void {
    $dispatcher = resolve(EventDispatcher::class);
    $dispatcher->dispatchSilently('');
})->throwsNoExceptions();

it('dispatchSilently creates event for valid names', function (): void {
    $dispatcher = resolve(EventDispatcher::class);
    $dispatcher->dispatchSilently('silent_event', ['source' => 'test']);

    assertDatabaseHas('glimpse_events', [
        'name' => 'silent_event',
        'properties' => json_encode(['source' => 'test'], JSON_THROW_ON_ERROR),
    ]);
});

it('dispatchSilently does not create event for invalid names', function (): void {
    $dispatcher = resolve(EventDispatcher::class);
    $dispatcher->dispatchSilently('invalid name!');

    assertDatabaseCount('glimpse_events', 0);
});

it('dispatchSilently does not dispatch event when glimpse is disabled', function (): void {
    config(['glimpse.enabled' => false]);
    $dispatcher = resolve(EventDispatcher::class);

    $dispatcher->dispatchSilently('any_event');

    assertDatabaseCount('glimpse_events', 0);
});

it('creates event with current session hash when none provided', function (): void {
    $dispatcher = resolve(EventDispatcher::class);
    $sessionHash = hash('sha256', 'test-session-id');

    $dispatcher->dispatch('session_event', [], $sessionHash);

    assertDatabaseHas('glimpse_events', [
        'name' => 'session_event',
        'session_hash' => $sessionHash,
    ]);
});

it('dispatches Laravel event after creating event', function (): void {
    Event::listen(GlimpseEventDispatchedEvent::class, function ($event): void {
        expect($event->name)->toBe('test_event')
            ->and($event->payload)->toBe(['key' => 'value'])
            ->and($event->sessionHash)->toBe('test-hash');
    });

    $dispatcher = resolve(EventDispatcher::class);
    $dispatcher->dispatch('test_event', ['key' => 'value'], 'test-hash');
});

it('stores created_at timestamp on event', function (): void {
    $dispatcher = resolve(EventDispatcher::class);
    $dispatcher->dispatch('timestamped_event');

    assertDatabaseHas('glimpse_events', [
        'name' => 'timestamped_event',
    ]);
});
