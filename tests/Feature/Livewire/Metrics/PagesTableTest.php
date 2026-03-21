<?php

declare(strict_types=1);

use Illuminate\Support\Collection;
use LaravelGlimpse\Livewire\Metrics\PagesTable;
use LaravelGlimpse\Values\DateRange;
use Livewire\Livewire;

it('renders with empty data when no aggregates exist', function (): void {
    Livewire::test(PagesTable::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewIs('glimpse::livewire.metrics.pages-table')
        ->assertViewHas('pages', fn (Collection $pages) => $pages->isEmpty())
        ->assertViewHas('max', 1);
});

it('renders pages data from aggregate data', function (): void {
    seedAggregate('page_views', 'path:/home', 0, 500);
    seedAggregate('page_views', 'path:/about', 0, 300);
    seedAggregate('page_views', 'path:/contact', 0, 100);

    Livewire::test(PagesTable::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewIs('glimpse::livewire.metrics.pages-table')
        ->assertViewHas('pages', fn (Collection $pages): bool => $pages->count() === 3
            && $pages->firstWhere('path', '/home')['views'] === 500
            && $pages->firstWhere('path', '/about')['views'] === 300
            && $pages->firstWhere('path', '/contact')['views'] === 100);
});

it('orders pages by views descending', function (): void {
    seedAggregate('page_views', 'path:/low', 0, 50);
    seedAggregate('page_views', 'path:/high', 0, 500);
    seedAggregate('page_views', 'path:/medium', 0, 200);

    Livewire::test(PagesTable::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewHas('pages', function (Collection $pages): bool {
            $pagesArray = $pages->values()->toArray();

            return $pagesArray[0]['path'] === '/high'
                && $pagesArray[0]['views'] === 500
                && $pagesArray[1]['path'] === '/medium'
                && $pagesArray[1]['views'] === 200
                && $pagesArray[2]['path'] === '/low'
                && $pagesArray[2]['views'] === 50;
        });
});

it('limits pages to 20 by default', function (): void {
    for ($i = 1; $i <= 25; $i++) {
        seedAggregate('page_views', "path:/page-{$i}", 0, $i * 10);
    }

    Livewire::test(PagesTable::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewHas('pages', fn (Collection $pages): bool => $pages->count() === 20);
});

it('calculates max correctly', function (): void {
    seedAggregate('page_views', 'path:/home', 0, 500);
    seedAggregate('page_views', 'path:/about', 0, 300);
    seedAggregate('page_views', 'path:/contact', 0, 100);

    Livewire::test(PagesTable::class, [
        'dateRange' => DateRange::today(),
    ])
        ->assertOk()
        ->assertViewHas('max', 500);
});

it('generates unique key based on date range', function (): void {
    $todayRange = DateRange::today();
    $yesterdayRange = DateRange::yesterday();

    $todayComponent = Livewire::test(PagesTable::class, ['dateRange' => $todayRange]);
    $yesterdayComponent = Livewire::test(PagesTable::class, ['dateRange' => $yesterdayRange]);

    $todayComponent->assertOk();
    $yesterdayComponent->assertOk();

    $todayKey = $todayComponent->instance()->getKey();
    $yesterdayKey = $yesterdayComponent->instance()->getKey();

    expect($todayKey)->not->toBe($yesterdayKey)
        ->and($todayKey)->toContain($todayRange->from->toDateString())
        ->and($yesterdayKey)->toContain($yesterdayRange->from->toDateString());
});
