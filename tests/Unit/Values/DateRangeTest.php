<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use LaravelGlimpse\Values\DateRange;

it('creates a today range', function (): void {
    $range = DateRange::today();

    expect($range->from->isToday())->toBeTrue()
        ->and($range->to->isToday())->toBeTrue()
        ->and($range->preset)->toBe('today');
});

it('creates a last-7-days range spanning 7 days', function (): void {
    $range = DateRange::last7Days();

    expect($range->diffInDays())->toBe(6); // inclusive: day 0 through day 6
});

it('creates a last-30-days range spanning 30 days', function (): void {
    $range = DateRange::last30Days();

    expect($range->diffInDays())->toBe(29);
});

it('resolves a preset string via fromPreset()', function (string $preset): void {
    expect(DateRange::fromPreset($preset)->preset)->toBe($preset);
})->with([
    'today', 'yesterday', '30d', '90d', 'month', '7d',
]);

it('falls back to last 7 days for unknown preset', function (): void {
    $range = DateRange::fromPreset('unknown');

    expect($range->preset)->toBe('7d');
});

it('computes the previous period correctly', function (): void {
    $range = DateRange::last7Days();
    $prev = $range->previousPeriod();

    // Previous period should end the day before $range starts
    expect($prev->to->toDateString())->toBe($range->from->copy()->subDay()->toDateString());
});

it('generates correct labels for all presets', function (string $preset, string $label): void {
    expect(DateRange::fromPreset($preset)->label())->toBe(__($label));
})->with([
    'today' => ['today', 'glimpse::messages.today'],
    'yesterday' => ['yesterday', 'glimpse::messages.yesterday'],
    '7d' => ['7d', 'glimpse::messages.last_7_days'],
    '30d' => ['30d', 'glimpse::messages.last_30_days'],
    '90d' => ['90d', 'glimpse::messages.last_90_days'],
    'month' => ['month', 'glimpse::messages.this_month'],
]);

it('returns all preset options', function (): void {
    $presets = DateRange::presets();

    expect($presets)->toHaveKey('today')
        ->toHaveKey('7d')
        ->toHaveKey('30d')
        ->toHaveKey('90d')
        ->toHaveKey('month');
});

it('custom range stores from/to correctly', function (): void {
    $from = Date::parse('2024-01-01');
    $to = Date::parse('2024-01-31');
    $range = DateRange::custom($from, $to);

    expect($range->from->toDateString())->toBe('2024-01-01')
        ->and($range->to->toDateString())->toBe('2024-01-31')
        ->and($range->preset)->toBe('custom');
});

it('formats custom range correctly', function (): void {
    $from = Date::parse('2024-01-01');
    $to = Date::parse('2024-01-31');
    $range = DateRange::custom($from, $to);

    expect((string) $range)->toBe('[custom] 2024-01-01 00:00:00 → 2024-01-31 23:59:59');
});
