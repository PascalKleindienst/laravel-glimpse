<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use LaravelGlimpse\Contracts\QueryServiceContract;
use LaravelGlimpse\Values\DateRange;

// ---------------------------------------------------------------------------
// summary()
// ---------------------------------------------------------------------------

it('returns zeroed summary when no data exists', function (): void {
    $service = resolve(QueryServiceContract::class);
    $summary = $service->summary(DateRange::today());

    expect($summary['visitors'])->toBe(0)
        ->and($summary['page_views'])->toBe(0)
        ->and($summary['bounce_rate'])->toBe(0.0)
        ->and($summary['avg_duration'])->toBe(0.0);
});

it('returns correct summary values from aggregate data', function (): void {
    seedAggregate('visitors', null, 100, 100);
    seedAggregate('page_views', null, 350, 350);
    seedAggregate('bounce_rate', null, 42.5, 100);
    seedAggregate('avg_duration', null, 127.3, 100);

    $service = resolve(QueryServiceContract::class);
    $summary = $service->summary(DateRange::today());

    expect($summary['visitors'])->toBe(100)
        ->and($summary['page_views'])->toBe(350)
        ->and($summary['bounce_rate'])->toBe(42.5)
        ->and($summary['avg_duration'])->toBe(127.3);
});

// ---------------------------------------------------------------------------
// topPages()
// ---------------------------------------------------------------------------

it('returns top pages sorted by view count', function (): void {
    seedAggregate('page_views', 'path:/home', 0, 500);
    seedAggregate('page_views', 'path:/pricing', 0, 200);
    seedAggregate('page_views', 'path:/about', 0, 50);

    $pages = resolve(QueryServiceContract::class)->topPages(DateRange::today(), 10);

    expect($pages->first()['path'])->toBe('/home')
        ->and($pages->first()['views'])->toBe(500)
        ->and($pages->count())->toBe(3);
});

it('strips the path: prefix correctly from top pages', function (): void {
    seedAggregate('page_views', 'path:/contact', 0, 10);

    $pages = resolve(QueryServiceContract::class)->topPages(DateRange::today());

    expect($pages->first()['path'])->toBe('/contact');
});

// ---------------------------------------------------------------------------
// topChannels()
// ---------------------------------------------------------------------------

it('returns channels sorted by visitor count', function (): void {
    seedAggregate('visitors', 'channel:organic', 0, 300);
    seedAggregate('visitors', 'channel:direct', 0, 150);
    seedAggregate('visitors', 'channel:social', 0, 75);

    $channels = resolve(QueryServiceContract::class)->topChannels(DateRange::today());

    expect($channels->first()['channel'])->toBe('organic')
        ->and($channels->first()['visitors'])->toBe(300);
});

// ---------------------------------------------------------------------------
// platformBreakdown()
// ---------------------------------------------------------------------------

it('calculates platform percentages correctly', function (): void {
    seedAggregate('visitors', 'platform:desktop', 0, 600);
    seedAggregate('visitors', 'platform:mobile', 0, 350);
    seedAggregate('visitors', 'platform:tablet', 0, 50);

    $platforms = resolve(QueryServiceContract::class)->platformBreakdown(DateRange::today());

    $desktop = $platforms->firstWhere('platform', 'desktop');
    $mobile = $platforms->firstWhere('platform', 'mobile');

    expect($desktop['percentage'])->toBe(60.0)
        ->and($mobile['percentage'])->toBe(35.0);
});

// ---------------------------------------------------------------------------
// topEvents()
// ---------------------------------------------------------------------------

it('returns events sorted by occurrence count', function (): void {
    seedAggregate('events', 'event:signup', 0, 80);
    seedAggregate('events', 'event:checkout', 0, 30);

    $events = resolve(QueryServiceContract::class)->topEvents(DateRange::today());

    expect($events->first()['event'])->toBe('signup')
        ->and($events->first()['count'])->toBe(80);
});

// ---------------------------------------------------------------------------
// timeSeries()
// ---------------------------------------------------------------------------

it('generates time series with zero-filled buckets for each day', function (): void {
    // Only seed data for 2 of the 7 days
    seedAggregate('visitors', null, 0, 10, 'daily', Date::today()->subDays(3)->toDateString());
    seedAggregate('page_views', null, 0, 30, 'daily', Date::today()->subDays(3)->toDateString());

    $series = resolve(QueryServiceContract::class)->timeSeries(DateRange::last7Days());

    // Should have 7 buckets (one per day)
    expect($series->count())->toBe(7);

    // The seeded day should have data
    $seededBucket = $series->firstWhere('visitors', 10);

    expect($seededBucket)->not->toBeNull()
        ->and($seededBucket['page_views'])->toBe(30);

    // Other days should be zeroed
    $emptyBuckets = $series->where('visitors', 0);
    expect($emptyBuckets->count())->toBe(6);
});

// ---------------------------------------------------------------------------
// topReferrers()
// ---------------------------------------------------------------------------

it('returns referrers sorted by visitor count', function (): void {
    seedAggregate('visitors', 'referrer:google.com', 0, 500);
    seedAggregate('visitors', 'referrer:twitter.com', 0, 200);
    seedAggregate('visitors', 'referrer:github.com', 0, 50);

    $referrers = resolve(QueryServiceContract::class)->topReferrers(DateRange::today());

    expect($referrers->first()['domain'])->toBe('google.com')
        ->and($referrers->first()['visitors'])->toBe(500)
        ->and($referrers->count())->toBe(3);
});

it('strips the referrer: prefix correctly from top referrers', function (): void {
    seedAggregate('visitors', 'referrer:example.org', 0, 10);

    $referrers = resolve(QueryServiceContract::class)->topReferrers(DateRange::today());

    expect($referrers->first()['domain'])->toBe('example.org');
});

// ---------------------------------------------------------------------------
// topCountries()
// ---------------------------------------------------------------------------

it('returns countries sorted by visitor count', function (): void {
    seedAggregate('visitors', 'country:US', 0, 800);
    seedAggregate('visitors', 'country:DE', 0, 300);
    seedAggregate('visitors', 'country:FR', 0, 150);

    $countries = resolve(QueryServiceContract::class)->topCountries(DateRange::today());

    expect($countries->first()['country_code'])->toBe('US')
        ->and($countries->first()['visitors'])->toBe(800)
        ->and($countries->count())->toBe(3);
});

// ---------------------------------------------------------------------------
// topCities()
// ---------------------------------------------------------------------------

it('returns cities sorted by visitor count', function (): void {
    seedAggregate('visitors', 'city:New York', 0, 400);
    seedAggregate('visitors', 'city:London', 0, 250);
    seedAggregate('visitors', 'city:Berlin', 0, 100);

    $cities = resolve(QueryServiceContract::class)->topCities(DateRange::today());

    expect($cities->first()['city'])->toBe('New York')
        ->and($cities->first()['visitors'])->toBe(400)
        ->and($cities->count())->toBe(3);
});

// ---------------------------------------------------------------------------
// topLanguages()
// ---------------------------------------------------------------------------

it('returns languages sorted by visitor count', function (): void {
    seedAggregate('visitors', 'language:en', 0, 600);
    seedAggregate('visitors', 'language:de', 0, 200);
    seedAggregate('visitors', 'language:fr', 0, 100);

    $languages = resolve(QueryServiceContract::class)->topLanguages(DateRange::today());

    expect($languages->first()['language'])->toBe('en')
        ->and($languages->first()['visitors'])->toBe(600)
        ->and($languages->count())->toBe(3);
});

// ---------------------------------------------------------------------------
// topBrowsers()
// ---------------------------------------------------------------------------

it('returns browsers sorted by visitor count', function (): void {
    seedAggregate('visitors', 'browser:Chrome', 0, 700);
    seedAggregate('visitors', 'browser:Firefox', 0, 200);
    seedAggregate('visitors', 'browser:Safari', 0, 100);

    $browsers = resolve(QueryServiceContract::class)->topBrowsers(DateRange::today());

    expect($browsers->first()['browser'])->toBe('Chrome')
        ->and($browsers->first()['visitors'])->toBe(700)
        ->and($browsers->count())->toBe(3);
});

// ---------------------------------------------------------------------------
// topOs()
// ---------------------------------------------------------------------------

it('returns operating systems sorted by visitor count', function (): void {
    seedAggregate('visitors', 'os:Windows', 0, 500);
    seedAggregate('visitors', 'os:macOS', 0, 300);
    seedAggregate('visitors', 'os:Linux', 0, 100);

    $os = resolve(QueryServiceContract::class)->topOs(DateRange::today());

    expect($os->first()['os'])->toBe('Windows')
        ->and($os->first()['visitors'])->toBe(500)
        ->and($os->count())->toBe(3);
});

// ---------------------------------------------------------------------------
// previousPeriodSummary()
// ---------------------------------------------------------------------------

it('queries the previous equivalent period for comparison', function (): void {
    // Seed week
    seedAggregate('visitors', null, 0, 50, 'daily', today()->toDateString());
    // Seed last week
    seedAggregate('visitors', null, 0, 40, 'daily', today()->subWeek()->toDateString());

    $service = resolve(QueryServiceContract::class);
    $current = $service->summary(DateRange::last7Days());
    $previous = $service->previousPeriodSummary(DateRange::last7Days());

    expect($current['visitors'])->toBe(50)
        ->and($previous['visitors'])->toBe(40);
});
