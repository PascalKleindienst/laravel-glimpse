<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use LaravelGlimpse\Livewire\Metrics\DevicesBreakdown;
use LaravelGlimpse\Values\DateRange;
use Livewire\Livewire;

it('renders with empty data when no aggregates exist', function (): void {
    Livewire::test(DevicesBreakdown::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertSee('Devices')
        ->assertViewIs('glimpse::livewire.metrics.devices-breakdown')
        ->assertViewHas('tab', 'platforms')
        ->assertViewHas('platforms', fn ($platforms) => $platforms->isEmpty())
        ->assertViewHas('browsers', fn ($browsers) => $browsers->isEmpty())
        ->assertViewHas('os', fn ($os) => $os->isEmpty());
});

it('renders platforms data from aggregate data', function (): void {
    seedAggregate('visitors', 'platform:desktop', 0, 600);
    seedAggregate('visitors', 'platform:mobile', 0, 350);
    seedAggregate('visitors', 'platform:tablet', 0, 50);

    Livewire::test(DevicesBreakdown::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewIs('glimpse::livewire.metrics.devices-breakdown')
        ->assertViewHas('platforms', fn (Collection $platforms): bool => $platforms->count() === 3
            && $platforms->firstWhere('platform', 'desktop')['visitors'] === 600
            && $platforms->firstWhere('platform', 'desktop')['percentage'] === 60.0
            && $platforms->firstWhere('platform', 'mobile')['percentage'] === 35.0
            && $platforms->firstWhere('platform', 'tablet')['percentage'] === 5.0);
});

it('renders browsers data from aggregate data', function (): void {
    seedAggregate('visitors', 'browser:Chrome', 0, 700);
    seedAggregate('visitors', 'browser:Firefox', 0, 200);
    seedAggregate('visitors', 'browser:Safari', 0, 100);

    Livewire::test(DevicesBreakdown::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewIs('glimpse::livewire.metrics.devices-breakdown')
        ->assertViewHas('browsers', fn (Collection $browsers): bool => $browsers->count() === 3
            && $browsers->firstWhere('browser', 'Chrome')['visitors'] === 700
            && $browsers->firstWhere('browser', 'Firefox')['visitors'] === 200);
});

it('renders os data from aggregate data', function (): void {
    seedAggregate('visitors', 'os:Windows', 0, 500);
    seedAggregate('visitors', 'os:macOS', 0, 300);
    seedAggregate('visitors', 'os:Linux', 0, 100);

    Livewire::test(DevicesBreakdown::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewIs('glimpse::livewire.metrics.devices-breakdown')
        ->assertViewHas('os', fn (Collection $os): bool => $os->count() === 3
            && $os->firstWhere('os', 'Windows')['visitors'] === 500);
});

it('defaults to platforms tab', function (): void {
    Livewire::test(DevicesBreakdown::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewHas('tab', 'platforms');
});

it('can switch tab to browsers', function (): void {
    seedAggregate('visitors', 'browser:Chrome', 0, 100);

    Livewire::test(DevicesBreakdown::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->call('setTab', 'browsers')
        ->assertViewHas('tab', 'browsers');
});

it('can switch tab to os', function (): void {
    seedAggregate('visitors', 'os:Linux', 0, 50);

    Livewire::test(DevicesBreakdown::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->call('setTab', 'os')
        ->assertViewHas('tab', 'os');
});

it('calculates browser max correctly', function (): void {
    seedAggregate('visitors', 'browser:Chrome', 0, 700);
    seedAggregate('visitors', 'browser:Firefox', 0, 200);

    Livewire::test(DevicesBreakdown::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewHas('browserMax', 700);
});

it('calculates os max correctly', function (): void {
    seedAggregate('visitors', 'os:Windows', 0, 500);
    seedAggregate('visitors', 'os:macOS', 0, 300);

    Livewire::test(DevicesBreakdown::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewHas('osMax', 500);
});

it('generates unique key based on date range', function (): void {
    $todayRange = DateRange::today();
    $yesterdayRange = DateRange::yesterday();

    $todayComponent = Livewire::test(DevicesBreakdown::class, ['dateRange' => $todayRange]);
    $yesterdayComponent = Livewire::test(DevicesBreakdown::class, ['dateRange' => $yesterdayRange]);

    $todayComponent->assertOk();
    $yesterdayComponent->assertOk();

    $todayKey = $todayComponent->instance()->getKey();
    $yesterdayKey = $yesterdayComponent->instance()->getKey();

    expect($todayKey)->not->toBe($yesterdayKey)
        ->and($todayKey)->toContain($todayRange->from->toDateString())
        ->and($yesterdayKey)->toContain($yesterdayRange->from->toDateString());
});
