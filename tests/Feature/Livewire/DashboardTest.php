<?php

declare(strict_types=1);

use LaravelGlimpse\Livewire\Dashboard;
use LaravelGlimpse\Values\DateRange;
use Livewire\Livewire;

it('uses 7d preset by default', function (): void {
    $component = Livewire::test(Dashboard::class);
    $component->assertSet('preset', '7d');
});

it('can switch preset to ', function ($preset): void {
    Livewire::test(Dashboard::class)
        ->call('setPreset', $preset)
        ->assertSet('preset', $preset);
})->with([
    'today', 'yesterday', '7d', '30d', '90d', 'month',
]);

it('computes dateRange from preset', function (): void {
    $component = Livewire::test(Dashboard::class);

    expect($component->dateRange)->toBeInstanceOf(DateRange::class);
});

it('computes summary from dateRange', function (): void {
    $component = Livewire::test(Dashboard::class);

    expect($component->summary)->toBeArray();
});

it('computes previousSummary from dateRange', function (): void {
    $component = Livewire::test(Dashboard::class);

    expect($component->previousSummary)->toBeArray();
});

it('renders with empty summary when no aggregates exist', function (): void {
    $component = Livewire::test(Dashboard::class);
    $summary = $component->instance()->summary;

    expect($summary)->toBe([
        'visitors' => 0,
        'page_views' => 0,
        'sessions' => 0,
        'bounce_rate' => 0.0,
        'avg_duration' => 0.0,
        'avg_time_on_page' => 0.0,
    ]);
});

it('renders with empty previousSummary when no aggregates exist', function (): void {
    $component = Livewire::test(Dashboard::class);
    $previousSummary = $component->instance()->previousSummary;

    expect($previousSummary)->toBe([
        'visitors' => 0,
        'page_views' => 0,
        'sessions' => 0,
        'bounce_rate' => 0.0,
        'avg_duration' => 0.0,
        'avg_time_on_page' => 0.0,
    ]);
});

it('renders summary with data from aggregates', function (): void {
    seedAggregate('visitors', null, 0, 100, 'daily', today()->toDateString());
    seedAggregate('page_views', null, 0, 350, 'daily', today()->toDateString());
    seedAggregate('bounce_rate', null, 50.0, 10, 'daily', today()->toDateString());
    seedAggregate('avg_duration', null, 120.5, 10, 'daily', today()->toDateString());

    $component = Livewire::test(Dashboard::class);
    $summary = $component->instance()->summary;

    expect($summary)->toMatchArray([
        'visitors' => 100,
        'page_views' => 350,
        'sessions' => 100,
    ]);
});
