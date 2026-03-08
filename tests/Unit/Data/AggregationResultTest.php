<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Context;
use LaravelGlimpse\Data\AggregationResult;

it('creates AggregationResult with all properties', function (): void {
    $from = CarbonImmutable::parse('2024-01-01 00:00:00');
    $to = CarbonImmutable::parse('2024-01-02 00:00:00');

    $result = new AggregationResult(
        from: $from,
        to: $to,
        sessionsProcessed: 100,
        pageViewsProcessed: 500,
        eventsProcessed: 50,
        duration: 125.5,
    );

    expect($result->from)->toBe($from)
        ->and($result->to)->toBe($to)
        ->and($result->sessionsProcessed)->toBe(100)
        ->and($result->pageViewsProcessed)->toBe(500)
        ->and($result->eventsProcessed)->toBe(50)
        ->and($result->duration)->toBe(125.5);
});

it('has default values', function (): void {
    $from = CarbonImmutable::parse('2024-01-01 00:00:00');
    $to = CarbonImmutable::parse('2024-01-02 00:00:00');

    $result = new AggregationResult($from, $to);

    expect($result->sessionsProcessed)->toBe(0)
        ->and($result->pageViewsProcessed)->toBe(0)
        ->and($result->eventsProcessed)->toBe(0)
        ->and($result->duration)->toBe(0.0);
});

it('converts to string', function (): void {
    $from = CarbonImmutable::parse('2024-01-01 10:00:00');
    $to = CarbonImmutable::parse('2024-01-01 11:00:00');

    $result = new AggregationResult($from, $to, sessionsProcessed: 10, pageViewsProcessed: 50, eventsProcessed: 5, duration: 25.5);

    expect((string) $result)->toBe('Aggregated 2024-01-01 10:00:00 → 2024-01-01 11:00:00 | sessions=10 page_views=50 events=5 [25.50ms]');
});

it('returns summary as string', function (): void {
    $result = new AggregationResult(
        CarbonImmutable::now(),
        CarbonImmutable::now(),
        sessionsProcessed: 10,
    );

    expect($result->summary())->toBe((string) $result);
});

it('converts to array', function (): void {
    $from = CarbonImmutable::parse('2026-01-01 01:00:00');
    $to = CarbonImmutable::parse('2026-01-02 02:00:00');

    $result = new AggregationResult(
        from: $from,
        to: $to,
        sessionsProcessed: 100,
        pageViewsProcessed: 500,
        eventsProcessed: 50,
        duration: 125.5,
    );

    expect($result->toArray())->toBe([
        'sessionsProcessed' => 100,
        'pageViewsProcessed' => 500,
        'eventsProcessed' => 50,
        'duration' => 125.5,
        'from' => '2026-01-01 01:00:00',
        'to' => '2026-01-02 02:00:00',
    ]);
});

it('implements Arrayable', function (): void {
    $result = new AggregationResult(CarbonImmutable::now(), CarbonImmutable::now());

    expect($result)->toBeInstanceOf(Arrayable::class);
});

it('creates new instance from existing one', function (): void {
    $from = CarbonImmutable::parse('2024-01-01 00:00:00');
    $to = CarbonImmutable::parse('2024-01-02 00:00:00');
    Context::add(AggregationResult::CONTEXT_PAGE_VIEWS, 42);
    Context::add(AggregationResult::CONTEXT_SESSIONS, 13);
    Context::add(AggregationResult::CONTEXT_EVENTS, 37);

    $result = AggregationResult::from($from, $to, 123.45);

    expect($result)->toBeInstanceOf(AggregationResult::class)
        ->and($result->from)->toBe($from)
        ->and($result->to)->toBe($to)
        ->and($result->duration)->toBe(123.45)
        ->and($result->sessionsProcessed)->toBe(13)
        ->and($result->pageViewsProcessed)->toBe(42)
        ->and($result->eventsProcessed)->toBe(37);
});
