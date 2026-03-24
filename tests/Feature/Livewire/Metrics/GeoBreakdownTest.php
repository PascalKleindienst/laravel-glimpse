<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use LaravelGlimpse\Livewire\Metrics\GeoBreakdown;
use LaravelGlimpse\Values\DateRange;
use Livewire\Livewire;

it('renders with empty data when no aggregates exist', function (): void {
    Livewire::test(GeoBreakdown::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewIs('glimpse::livewire.metrics.geo-breakdown')
        ->assertViewHas('tab', 'countries')
        ->assertViewHas('countries', fn (Collection $countries) => $countries->isEmpty())
        ->assertViewHas('cities', fn (Collection $cities) => $cities->isEmpty())
        ->assertViewHas('languages', fn (Collection $languages) => $languages->isEmpty())
        ->assertViewHas('countryMax', 1)
        ->assertViewHas('cityMax', 1)
        ->assertViewHas('languageMax', 1);
});

it('renders countries data from aggregate data', function (): void {
    seedAggregate('visitors', 'country:US', 0, 600);
    seedAggregate('visitors', 'country:GB', 0, 300);
    seedAggregate('visitors', 'country:DE', 0, 100);

    Livewire::test(GeoBreakdown::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewIs('glimpse::livewire.metrics.geo-breakdown')
        ->assertViewHas('countries', fn (Collection $countries): bool => $countries->count() === 3
            && $countries->firstWhere(static fn (array $item): bool => $item['country']->iso === 'US')['visitors'] === 600
            && $countries->firstWhere(static fn (array $item): bool => $item['country']->iso === 'GB')['visitors'] === 300
            && $countries->firstWhere(static fn (array $item): bool => $item['country']->iso === 'DE')['visitors'] === 100);
});

it('renders cities data from aggregate data', function (): void {
    seedAggregate('visitors', 'city:New York', 0, 400);
    seedAggregate('visitors', 'city:London', 0, 200);
    seedAggregate('visitors', 'city:Berlin', 0, 100);

    Livewire::test(GeoBreakdown::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewIs('glimpse::livewire.metrics.geo-breakdown')
        ->assertViewHas('cities', fn (Collection $cities): bool => $cities->count() === 3
            && $cities->firstWhere('city', 'New York')['visitors'] === 400
            && $cities->firstWhere('city', 'London')['visitors'] === 200
            && $cities->firstWhere('city', 'Berlin')['visitors'] === 100);
});

it('renders languages data from aggregate data', function (): void {
    seedAggregate('visitors', 'language:en', 0, 700);
    seedAggregate('visitors', 'language:de', 0, 200);
    seedAggregate('visitors', 'language:fr', 0, 100);

    Livewire::test(GeoBreakdown::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewIs('glimpse::livewire.metrics.geo-breakdown')
        ->assertViewHas('languages', fn (Collection $languages): bool => $languages->count() === 3
            && $languages->firstWhere('language', 'en')['visitors'] === 700
            && $languages->firstWhere('language', 'de')['visitors'] === 200
            && $languages->firstWhere('language', 'fr')['visitors'] === 100);
});

it('defaults to countries tab', function (): void {
    Livewire::test(GeoBreakdown::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewHas('tab', 'countries');
});

it('can switch tab to cities', function (): void {
    seedAggregate('visitors', 'city:Tokyo', 0, 100);

    Livewire::test(GeoBreakdown::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->call('setTab', 'cities')
        ->assertViewHas('tab', 'cities');
});

it('can switch tab to languages', function (): void {
    seedAggregate('visitors', 'language:es', 0, 50);

    Livewire::test(GeoBreakdown::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->call('setTab', 'languages')
        ->assertViewHas('tab', 'languages');
});

it('calculates country max correctly', function (): void {
    seedAggregate('visitors', 'country:US', 0, 700);
    seedAggregate('visitors', 'country:GB', 0, 200);

    Livewire::test(GeoBreakdown::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewHas('countryMax', 700);
});

it('calculates city max correctly', function (): void {
    seedAggregate('visitors', 'city:Paris', 0, 500);
    seedAggregate('visitors', 'city:Madrid', 0, 300);

    Livewire::test(GeoBreakdown::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewHas('cityMax', 500);
});

it('calculates language max correctly', function (): void {
    seedAggregate('visitors', 'language:en', 0, 800);
    seedAggregate('visitors', 'language:fr', 0, 200);

    Livewire::test(GeoBreakdown::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewHas('languageMax', 800);
});

it('generates unique key based on date range', function (): void {
    $todayRange = DateRange::today();
    $yesterdayRange = DateRange::yesterday();

    $todayComponent = Livewire::test(GeoBreakdown::class, ['dateRange' => $todayRange]);
    $yesterdayComponent = Livewire::test(GeoBreakdown::class, ['dateRange' => $yesterdayRange]);

    $todayComponent->assertOk();
    $yesterdayComponent->assertOk();

    $todayKey = $todayComponent->instance()->getKey();
    $yesterdayKey = $yesterdayComponent->instance()->getKey();

    expect($todayKey)->not->toBe($yesterdayKey)
        ->and($todayKey)->toContain($todayRange->from->toDateString())
        ->and($yesterdayKey)->toContain($yesterdayRange->from->toDateString());
});
