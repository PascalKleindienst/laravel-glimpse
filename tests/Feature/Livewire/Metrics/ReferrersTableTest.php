<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use LaravelGlimpse\Livewire\Metrics\ReferrersTable;
use LaravelGlimpse\Values\DateRange;
use Livewire\Livewire;

it('renders with empty data when no aggregates exist', function (): void {
    Livewire::test(ReferrersTable::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewIs('glimpse::livewire.metrics.referrers-table')
        ->assertViewHas('tab', 'channels')
        ->assertViewHas('channels', fn (Collection $channels) => $channels->isEmpty())
        ->assertViewHas('referrers', fn (Collection $referrers) => $referrers->isEmpty())
        ->assertViewHas('channelMax', 1)
        ->assertViewHas('referrerMax', 1);
});

it('renders channels data from aggregate data', function (): void {
    seedAggregate('visitors', 'channel:Organic', 0, 600);
    seedAggregate('visitors', 'channel:Direct', 0, 300);
    seedAggregate('visitors', 'channel:Social', 0, 100);

    Livewire::test(ReferrersTable::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewIs('glimpse::livewire.metrics.referrers-table')
        ->assertViewHas('channels', fn (Collection $channels): bool => $channels->count() === 3
            && $channels->firstWhere('channel', 'Organic')['visitors'] === 600
            && $channels->firstWhere('channel', 'Direct')['visitors'] === 300
            && $channels->firstWhere('channel', 'Social')['visitors'] === 100);
});

it('renders referrers data from aggregate data', function (): void {
    seedAggregate('visitors', 'referrer:google.com', 0, 500);
    seedAggregate('visitors', 'referrer:twitter.com', 0, 200);
    seedAggregate('visitors', 'referrer:facebook.com', 0, 100);

    Livewire::test(ReferrersTable::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewIs('glimpse::livewire.metrics.referrers-table')
        ->assertViewHas('referrers', fn (Collection $referrers): bool => $referrers->count() === 3
            && $referrers->firstWhere('domain', 'google.com')['visitors'] === 500
            && $referrers->firstWhere('domain', 'twitter.com')['visitors'] === 200
            && $referrers->firstWhere('domain', 'facebook.com')['visitors'] === 100);
});

it('defaults to channels tab', function (): void {
    Livewire::test(ReferrersTable::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewHas('tab', 'channels');
});

it('can switch tab to referrers', function (): void {
    seedAggregate('visitors', 'referrer:bing.com', 0, 100);

    Livewire::test(ReferrersTable::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->call('setTab', 'referrers')
        ->assertViewHas('tab', 'referrers');
});

it('can switch tab back to channels', function (): void {
    seedAggregate('visitors', 'channel:Email', 0, 50);

    Livewire::test(ReferrersTable::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->call('setTab', 'referrers')
        ->assertOk()
        ->call('setTab', 'channels')
        ->assertViewHas('tab', 'channels');
});

it('calculates channel max correctly', function (): void {
    seedAggregate('visitors', 'channel:Organic', 0, 700);
    seedAggregate('visitors', 'channel:Direct', 0, 200);

    Livewire::test(ReferrersTable::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewHas('channelMax', 700);
});

it('calculates referrer max correctly', function (): void {
    seedAggregate('visitors', 'referrer:example.com', 0, 500);
    seedAggregate('visitors', 'referrer:test.com', 0, 300);

    Livewire::test(ReferrersTable::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewHas('referrerMax', 500);
});

it('generates unique key based on date range', function (): void {
    $todayRange = DateRange::today();
    $yesterdayRange = DateRange::yesterday();

    $todayComponent = Livewire::test(ReferrersTable::class, ['dateRange' => $todayRange]);
    $yesterdayComponent = Livewire::test(ReferrersTable::class, ['dateRange' => $yesterdayRange]);

    $todayComponent->assertOk();
    $yesterdayComponent->assertOk();

    $todayKey = $todayComponent->instance()->getKey();
    $yesterdayKey = $yesterdayComponent->instance()->getKey();

    expect($todayKey)->not->toBe($yesterdayKey)
        ->and($todayKey)->toContain($todayRange->from->toDateString())
        ->and($yesterdayKey)->toContain($yesterdayRange->from->toDateString());
});
