<?php

declare(strict_types=1);

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
