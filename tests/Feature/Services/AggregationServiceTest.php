<?php

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------
use Illuminate\Support\Facades\Date;
use LaravelGlimpse\Contracts\AggregationServiceContract;
use LaravelGlimpse\Models\GlimpseAggregate;
use LaravelGlimpse\Models\GlimpseEvent;
use LaravelGlimpse\Models\GlimpsePageView;
use LaravelGlimpse\Models\GlimpseSession;

use function Pest\Laravel\assertDatabaseHas;

function makeSession(array $overrides = []): GlimpseSession
{
    static $i = 0;
    $i++;

    return GlimpseSession::query()->create(array_merge([
        'session_hash' => "hash-{$i}",
        'ip_hash' => 'ip-hash',
        'page_view_count' => 1,
        'duration_seconds' => 0,
        'is_bounce' => true,
        'started_at' => Date::now(),
        'last_seen_at' => Date::now(),
    ], $overrides));
}

function makePageView(string $sessionHash, array $overrides = []): GlimpsePageView
{
    return GlimpsePageView::query()->create(array_merge([
        'session_hash' => $sessionHash,
        'url' => 'http://example.test/page',
        'path' => '/page',
        'created_at' => Date::now(),
    ], $overrides));
}

function makeEvent(string $name, ?string $sessionHash = null, array $overrides = []): GlimpseEvent
{
    return GlimpseEvent::query()->create(array_merge([
        'session_hash' => $sessionHash,
        'name' => $name,
        'created_at' => Date::now(),
    ], $overrides));
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('visitor and session metrics', function (): void {
    it('writes visitors and sessions counts', function (): void {
        $now = Date::now();

        makeSession(['started_at' => $now, 'last_seen_at' => $now]);
        makeSession(['started_at' => $now, 'last_seen_at' => $now]);

        app(AggregationServiceContract::class)->aggregate($now->copy()->subMinute(), $now->copy()->addMinute());

        $visitors = GlimpseAggregate::query()->where('metric', 'visitors')->where('dimension', '-')->first();
        expect($visitors)->not->toBeNull()
            ->and($visitors->count)->toBe(2);
    });

    it('computes bounce_rate correctly', function (): void {
        $now = Date::now();

        makeSession(['started_at' => $now, 'is_bounce' => true,  'page_view_count' => 1]);
        makeSession(['started_at' => $now, 'is_bounce' => true,  'page_view_count' => 1]);
        makeSession(['started_at' => $now, 'is_bounce' => false, 'page_view_count' => 3]);

        app(AggregationServiceContract::class)->aggregate($now->copy()->subMinute(), $now->copy()->addMinute());

        $bounceRate = GlimpseAggregate::query()->where('metric', 'bounce_rate')->where('dimension', '-')->first();
        expect($bounceRate)->not->toBeNull()
            // 2 bounces / 3 sessions = 66.67
            ->and(round($bounceRate->value, 2))->toBe(66.67);
    });

    it('computes avg_duration', function (): void {
        $now = Date::now();

        makeSession(['started_at' => $now, 'duration_seconds' => 60]);
        makeSession(['started_at' => $now, 'duration_seconds' => 120]);

        app(AggregationServiceContract::class)->aggregate($now->copy()->subMinute(), $now->copy()->addMinute());

        $avg = GlimpseAggregate::query()->where('metric', 'avg_duration')->where('dimension', '-')->first();
        expect($avg->value)->toBe(90.0);
    });
});

describe('page view metrics', function (): void {
    it('writes total page_views count', function (): void {
        $now = Date::now();
        $sess = makeSession(['started_at' => $now]);

        makePageView($sess->session_hash, ['created_at' => $now]);
        makePageView($sess->session_hash, ['created_at' => $now]);
        makePageView($sess->session_hash, ['created_at' => $now]);

        app(AggregationServiceContract::class)->aggregate($now->copy()->subMinute(), $now->copy()->addMinute());

        $pv = GlimpseAggregate::query()
            ->where('metric', 'page_views')->where('dimension', '-')->first();
        expect($pv->count)->toBe(3);
    });

    it('writes page-level breakdown with path dimension', function (): void {
        $now = Date::now();
        $sess = makeSession(['started_at' => $now]);

        makePageView($sess->session_hash, ['path' => '/about',   'created_at' => $now]);
        makePageView($sess->session_hash, ['path' => '/about',   'created_at' => $now]);
        makePageView($sess->session_hash, ['path' => '/pricing', 'created_at' => $now]);

        app(AggregationServiceContract::class)->aggregate($now->copy()->subMinute(), $now->copy()->addMinute());

        $about = GlimpseAggregate::query()
            ->where('metric', 'page_views')
            ->where('dimension', 'path:/about')
            ->first();

        expect($about->count)->toBe(2);
    });
});

describe('custom event metrics', function (): void {
    it('writes event counts grouped by name', function (): void {
        $now = Date::now();

        makeEvent('signup', null, ['created_at' => $now]);
        makeEvent('signup', null, ['created_at' => $now]);
        makeEvent('checkout', null, ['created_at' => $now]);

        app(AggregationServiceContract::class)->aggregate($now->copy()->subMinute(), $now->copy()->addMinute());

        $signup = GlimpseAggregate::query()
            ->where('metric', 'events')
            ->where('dimension', 'event:signup')
            ->first();

        expect($signup?->count)->toBe(2);

        $checkout = GlimpseAggregate::query()
            ->where('metric', 'events')
            ->where('dimension', 'event:checkout')
            ->first();

        expect($checkout?->count)->toBe(1);
    });
});

describe('dimensional breakdown (country, browser, etc.)', function (): void {
    it('writes country dimension breakdown', function (): void {
        $now = Date::now();

        makeSession(['started_at' => $now, 'country_code' => 'DE']);
        makeSession(['started_at' => $now, 'country_code' => 'DE']);
        makeSession(['started_at' => $now, 'country_code' => 'US']);

        app(AggregationServiceContract::class)->aggregate($now->copy()->subMinute(), $now->copy()->addMinute());

        $de = GlimpseAggregate::query()
            ->where('metric', 'visitors')
            ->where('dimension', 'country:DE')
            ->first();

        expect($de->count)->toBe(2);
    });

    it('writes platform dimension breakdown', function (): void {
        $now = Date::now();

        makeSession(['started_at' => $now, 'platform' => 'mobile']);
        makeSession(['started_at' => $now, 'platform' => 'mobile']);
        makeSession(['started_at' => $now, 'platform' => 'desktop']);

        app(AggregationServiceContract::class)->aggregate($now->copy()->subMinute(), $now->copy()->addMinute());

        $mobile = GlimpseAggregate::query()
            ->where('metric', 'visitors')
            ->where('dimension', 'platform:mobile')
            ->first();

        expect($mobile->count)->toBe(2);
    });
});

describe('idempotency', function (): void {
    it('is idempotent — running twice produces the same result', function (): void {
        $now = Date::now();
        $from = $now->copy()->subMinute();
        $to = $now->copy()->addMinute();

        makeSession(['started_at' => $now]);
        makeSession(['started_at' => $now]);

        $service = app(AggregationServiceContract::class);
        $service->aggregate($from, $to);
        $service->aggregate($from, $to); // second run — should upsert, not duplicate

        $total = GlimpseAggregate::query()
            ->where('metric', 'visitors')
            ->where('dimension', '-')
            ->get('count')
            ->sum('count');
        expect((int) $total)->toBe(2);
    });
});

describe('hourly vs daily period selection', function (): void {
    it('writes hourly rows for narrow windows', function (): void {
        $now = Date::now();
        makeSession(['started_at' => $now]);

        app(AggregationServiceContract::class)->aggregate($now->copy()->subHour(), $now->copy()->addHour());

        assertDatabaseHas('glimpse_aggregates', [
            'period' => 'hourly',
            'metric' => 'visitors',
            'dimension' => '-',
        ]);
    });

    it('writes daily rows', function (): void {
        $now = Date::now();
        makeSession(['started_at' => $now]);

        app(AggregationServiceContract::class)->aggregate($now->copy()->subDays(3), $now->copy());

        assertDatabaseHas('glimpse_aggregates', [
            'period' => 'daily',
            'metric' => 'visitors',
            'dimension' => '-',
        ]);
    });
});
