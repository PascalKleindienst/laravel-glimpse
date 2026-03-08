<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use LaravelGlimpse\Values\AggregationPeriod;

it('creates custom period', function (): void {
    $start = Date::parse('2026-01-01 10:00:00');
    $end = Date::parse('2026-01-01 11:00:00');

    $period = AggregationPeriod::custom($start, $end);

    expect($period->start->toDateTimeString())->toBe('2026-01-01 10:00:00')
        ->and($period->end->toDateTimeString())->toBe('2026-01-01 11:00:00')
        ->and($period->period)->toBe('custom');
});

it('creates hourly period', function (): void {
    $start = Date::parse('2026-01-01 10:00:00');
    $end = Date::parse('2026-01-01 11:00:00');

    $period = AggregationPeriod::hourly($start, $end);

    expect($period->period)->toBe('hourly');
});

it('creates daily period', function (): void {
    $start = Date::parse('2026-01-01 00:00:00');
    $end = Date::parse('2026-01-02 00:00:00');

    $period = AggregationPeriod::daily($start, $end);

    expect($period->period)->toBe('daily');
});

it('creates copies of dates to ensure immutability', function (): void {
    $start = Date::parse('2026-01-01 10:00:00');
    $end = Date::parse('2026-01-01 11:00:00');

    $period = AggregationPeriod::custom($start, $end);

    $period->start->addHour();

    expect($period->start->toDateTimeString())->toBe('2026-01-01 11:00:00')
        ->and($start->toDateTimeString())->not->toBe($period->start->toDateTimeString());
});

it('converts to string', function (): void {
    $start = Date::parse('2026-01-01 10:00:00');
    $end = Date::parse('2026-01-01 11:00:00');

    $period = AggregationPeriod::hourly($start, $end);

    expect((string) $period)->toBe('[hourly] 2026-01-01 10:00:00 → 2026-01-01 11:00:00');
});

it('daily period formats correctly', function (): void {
    $start = Date::parse('2026-01-01 00:00:00');
    $end = Date::parse('2026-01-02 00:00:00');

    $period = AggregationPeriod::daily($start, $end);

    expect((string) $period)->toBe('[daily] 2026-01-01 00:00:00 → 2026-01-02 00:00:00');
});
