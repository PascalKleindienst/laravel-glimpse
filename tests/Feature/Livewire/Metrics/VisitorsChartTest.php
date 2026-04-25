<?php

declare(strict_types=1);

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;
use LaravelGlimpse\Livewire\Metrics\VisitorsChart;
use LaravelGlimpse\Values\DateRange;
use Livewire\Livewire;

it('renders with empty data when no aggregates exist', function (): void {
    Livewire::test(VisitorsChart::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewIs('glimpse::livewire.metrics.visitors-chart')
        ->assertSee(__('glimpse::messages.visitors_over_time'))
        ->assertViewHas('labels', fn (array $labels): bool => count($labels) === 24)
        ->assertViewHas('visitors', fn (array $visitors): bool => Arr::every($visitors, static fn (int $v): bool => $v === 0))
        ->assertViewHas('page_views', fn (array $views): bool => Arr::every($views, static fn (int $v): bool => $v === 0));
});

it('renders visitors and page views from time series data', function (): void {
    $date = Date::today()->subDays(2)->toDateString();

    seedAggregate('visitors', null, 0, 100, 'daily', $date);
    seedAggregate('page_views', null, 0, 350, 'daily', $date);

    Livewire::test(VisitorsChart::class, [
        'dateRange' => DateRange::last7Days(),
    ])
        ->assertOk()
        ->assertViewIs('glimpse::livewire.metrics.visitors-chart')
        ->assertViewHas('labels', fn (array $labels): bool => count($labels) === 7)
        ->assertViewHas('visitors', [0, 0, 0, 0, 100, 0, 0])
        ->assertViewHas('page_views', [0, 0, 0, 0, 350, 0, 0]);
});

it('renders correct number of data points for date range', function (): void {
    seedAggregate('visitors', null, 0, 10, 'daily', Date::today()->subDays(1)->toDateString());
    seedAggregate('visitors', null, 0, 20, 'daily', Date::today()->subDays(2)->toDateString());

    Livewire::test(VisitorsChart::class, [
        'dateRange' => DateRange::last7Days(),
    ])
        ->assertOk()
        ->assertViewIs('glimpse::livewire.metrics.visitors-chart')
        ->assertViewHas('labels', fn (array $labels): bool => count($labels) === 7)
        ->assertViewHas('visitors', [0, 0, 0, 0, 20, 10, 0])
        ->assertViewHas('page_views', [0, 0, 0, 0, 0, 0, 0]);
});

it('includes zero values for days without data', function (): void {
    $today = Date::today()->toDateString();
    seedAggregate('visitors', null, 0, 50, 'daily', $today);

    Livewire::test(VisitorsChart::class, [
        'dateRange' => DateRange::last7Days(),
    ])
        ->assertOk()
        ->assertViewIs('glimpse::livewire.metrics.visitors-chart')
        ->assertViewHas('labels', fn (array $labels): bool => count($labels) === 7)
        ->assertViewHas('visitors', [0, 0, 0, 0, 0, 0, 50])
        ->assertViewHas('page_views', [0, 0, 0, 0, 0, 0, 0]);
});

it('generates unique key based on date range', function (): void {
    $todayRange = DateRange::today();
    $yesterdayRange = DateRange::yesterday();

    $todayComponent = Livewire::test(VisitorsChart::class, ['dateRange' => $todayRange]);
    $yesterdayComponent = Livewire::test(VisitorsChart::class, ['dateRange' => $yesterdayRange]);

    $todayComponent->assertOk();
    $yesterdayComponent->assertOk();

    $todayKey = $todayComponent->instance()->getKey();
    $yesterdayKey = $yesterdayComponent->instance()->getKey();

    expect($todayKey)->not->toBe($yesterdayKey)
        ->and($todayKey)->toContain($todayRange->from->toDateString())
        ->and($yesterdayKey)->toContain($yesterdayRange->from->toDateString());
});
