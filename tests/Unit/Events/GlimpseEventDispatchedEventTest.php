<?php

declare(strict_types=1);

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LaravelGlimpse\Events\GlimpseEventDispatchedEvent;
use LaravelGlimpse\Models\GlimpseEvent;

it('creates event with all properties', function (): void {
    $mockEvent = GlimpseEvent::query()->newModelInstance();
    $payload = ['plan' => 'pro', 'value' => 99];

    $event = new GlimpseEventDispatchedEvent(
        name: 'checkout_completed',
        payload: $payload,
        sessionHash: 'abc123',
        event: $mockEvent,
    );

    expect($event->name)->toBe('checkout_completed')
        ->and($event->payload)->toBe($payload)
        ->and($event->sessionHash)->toBe('abc123')
        ->and($event->event)->toEqual($mockEvent);
});

it('creates event with null session hash', function (): void {
    $mockEvent = GlimpseEvent::query()->newModelInstance();
    $payload = ['step' => 1];

    $event = new GlimpseEventDispatchedEvent(
        name: 'page_viewed',
        payload: $payload,
        sessionHash: null,
        event: $mockEvent,
    );

    expect($event->name)->toBe('page_viewed')
        ->and($event->payload)->toBe($payload)
        ->and($event->sessionHash)->toBeNull()
        ->and($event->event)->toEqual($mockEvent);
});

it('creates event with empty payload', function (): void {
    $mockEvent = GlimpseEvent::query()->newModelInstance();

    $event = new GlimpseEventDispatchedEvent(
        name: 'simple_event',
        payload: [],
        sessionHash: 'session-xyz',
        event: $mockEvent,
    );

    expect($event->name)->toBe('simple_event')
        ->and($event->payload)->toBe([])
        ->and($event->sessionHash)->toBe('session-xyz');
});

it('creates event with nested payload', function (): void {
    $mockEvent = GlimpseEvent::query()->newModelInstance();
    $payload = [
        'user' => ['id' => 123, 'name' => 'John'],
        'metadata' => ['source' => 'api', 'count' => 5],
    ];

    $event = new GlimpseEventDispatchedEvent(
        name: 'complex_event',
        payload: $payload,
        sessionHash: null,
        event: $mockEvent,
    );

    expect($event->payload)->toBe($payload)
        ->and($event->payload['user']['id'])->toBe(123)
        ->and($event->payload['metadata']['source'])->toBe('api');
});

it('has Dispatchable trait', function (): void {
    $traits = class_uses(GlimpseEventDispatchedEvent::class);

    expect($traits)->toContain(Dispatchable::class)
        ->and($traits)->toContain(SerializesModels::class);
});
