<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use LaravelGlimpse\Livewire\Metrics\EventsTable;
use LaravelGlimpse\Values\DateRange;
use Livewire\Livewire;

it('renders with empty data when no aggregates exist', function (): void {
    Livewire::test(EventsTable::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewIs('glimpse::livewire.metrics.events-table')
        ->assertViewHas('events', fn (Collection $events) => $events->isEmpty())
        ->assertViewHas('max', 1);
});

it('renders events data from aggregate data', function (): void {
    seedAggregate('events', 'event:page_view', 0, 500);
    seedAggregate('events', 'event:click', 0, 300);
    seedAggregate('events', 'event:form_submit', 0, 100);

    Livewire::test(EventsTable::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewIs('glimpse::livewire.metrics.events-table')
        ->assertViewHas('events', fn (Collection $events): bool => $events->count() === 3
            && $events->firstWhere('event', 'page_view')['count'] === 500
            && $events->firstWhere('event', 'click')['count'] === 300
            && $events->firstWhere('event', 'form_submit')['count'] === 100);
});

it('orders events by count descending', function (): void {
    seedAggregate('events', 'event:low', 0, 50);
    seedAggregate('events', 'event:high', 0, 500);
    seedAggregate('events', 'event:medium', 0, 200);

    Livewire::test(EventsTable::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewHas('events', function (Collection $events): bool {
            $eventsArray = $events->values()->toArray();

            return $eventsArray[0]['event'] === 'high'
                && $eventsArray[0]['count'] === 500
                && $eventsArray[1]['event'] === 'medium'
                && $eventsArray[1]['count'] === 200
                && $eventsArray[2]['event'] === 'low'
                && $eventsArray[2]['count'] === 50;
        });
});

it('limits events to 20 by default', function (): void {
    for ($i = 1; $i <= 25; $i++) {
        seedAggregate('events', "event:event_{$i}", 0, $i * 10);
    }

    Livewire::test(EventsTable::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewHas('events', fn (Collection $events): bool => $events->count() === 20);
});

it('calculates max correctly', function (): void {
    seedAggregate('events', 'event:page_view', 0, 500);
    seedAggregate('events', 'event:click', 0, 300);
    seedAggregate('events', 'event:form_submit', 0, 100);

    Livewire::test(EventsTable::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewHas('max', 500);
});

it('generates unique key based on date range', function (): void {
    $todayRange = DateRange::today();
    $yesterdayRange = DateRange::yesterday();

    $todayComponent = Livewire::test(EventsTable::class, ['dateRange' => $todayRange]);
    $yesterdayComponent = Livewire::test(EventsTable::class, ['dateRange' => $yesterdayRange]);

    $todayComponent->assertOk();
    $yesterdayComponent->assertOk();

    $todayKey = $todayComponent->instance()->getKey();
    $yesterdayKey = $yesterdayComponent->instance()->getKey();

    expect($todayKey)->not->toBe($yesterdayKey)
        ->and($todayKey)->toContain($todayRange->from->toDateString())
        ->and($yesterdayKey)->toContain($yesterdayRange->from->toDateString());
});
