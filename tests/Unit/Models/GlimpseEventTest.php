<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LaravelGlimpse\Database\Factories\GlimpseEventFactory;
use LaravelGlimpse\Models\GlimpseEvent;

beforeEach(function (): void {
    $this->event = GlimpseEvent::factory()->create();
});

it('has correct table name', function (): void {
    expect($this->event->getTable())->toBe('glimpse_events');
});

it('has timestamps enabled', function (): void {
    expect($this->event->timestamps)->toBeTrue();
});

it('has session relationship', function (): void {
    expect($this->event->session())->toBeInstanceOf(BelongsTo::class);
});

it('casts properties to array', function (): void {
    $event = GlimpseEvent::factory()->create(['properties' => ['key' => 'value']]);

    expect($event->properties)->toBeArray()
        ->and($event->properties)->toBe(['key' => 'value']);
});

it('can create with nested properties', function (): void {
    $event = GlimpseEvent::factory()->create([
        'properties' => [
            'page' => '/home',
            'user_id' => 123,
            'metadata' => ['foo' => 'bar'],
        ],
    ]);

    expect($event->properties['page'])->toBe('/home')
        ->and($event->properties['user_id'])->toBe(123)
        ->and($event->properties['metadata']['foo'])->toBe('bar');
});

it('can be created with factory', function (): void {
    $event = GlimpseEvent::factory()->create();

    expect($event)->toBeInstanceOf(GlimpseEvent::class)
        ->and($event->name)->not->toBeEmpty();
});

it('can be created with custom attributes', function (): void {
    $event = GlimpseEvent::factory()->create([
        'name' => 'custom_event',
        'session_hash' => 'test-hash',
    ]);

    expect($event->name)->toBe('custom_event')
        ->and($event->session_hash)->toBe('test-hash');
});

it('uses GlimpseEventFactory', function (): void {
    expect(GlimpseEvent::factory())->toBeInstanceOf(GlimpseEventFactory::class);
});

it('can mass assign attributes', function (): void {
    $event = new GlimpseEvent([
        'name' => 'test_event',
        'session_hash' => 'hash-123',
        'properties' => ['test' => true],
    ]);

    expect($event->name)->toBe('test_event')
        ->and($event->session_hash)->toBe('hash-123')
        ->and($event->properties)->toBe(['test' => true]);
});
