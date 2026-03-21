<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use LaravelGlimpse\Models\GlimpseAggregate;
use LaravelGlimpse\Models\GlimpseEvent;
use LaravelGlimpse\Models\GlimpsePageView;
use LaravelGlimpse\Models\GlimpseSession;
use LaravelGlimpse\Services\PruneService;

function makeOldSession(int $daysAgo): GlimpseSession
{
    static $i = 0;
    $i++;

    $ts = Date::now()->subDays($daysAgo);

    return GlimpseSession::query()->create([
        'session_hash' => "prune-hash-{$i}",
        'page_view_count' => 1,
        'duration_seconds' => 0,
        'is_bounce' => true,
        'started_at' => $ts,
        'last_seen_at' => $ts,
    ]);
}

it('deletes raw sessions, page views, and events beyond the retention window', function (): void {
    config(['glimpse.retention.raw' => 30]);

    // Old data (should be pruned)
    $old = makeOldSession(45);
    GlimpsePageView::query()->create(['session_hash' => $old->session_hash, 'url' => '/x', 'path' => '/x', 'created_at' => Date::now()->subDays(45)]);
    GlimpseEvent::query()->create(['name' => 'old', 'created_at' => Date::now()->subDays(45)]);

    // Recent data (should survive)
    $recent = makeOldSession(5);
    GlimpsePageView::query()->create(['session_hash' => $recent->session_hash, 'url' => '/y', 'path' => '/y', 'created_at' => Date::now()->subDays(5)]);
    GlimpseEvent::query()->create(['name' => 'new', 'created_at' => Date::now()->subDays(5)]);

    $counts = resolve(PruneService::class)->prune();

    expect($counts['sessions'])->toBe(1)
        ->and($counts['page_views'])->toBe(1)
        ->and($counts['events'])->toBe(1)
        ->and(GlimpseSession::query()->count())->toBe(1)
        ->and(GlimpsePageView::query()->count())->toBe(1)
        ->and(GlimpseEvent::query()->count())->toBe(1);
});

it('does not prune aggregates when retention is null (forever)', function (): void {
    config(['glimpse.retention.aggregates' => null]);

    GlimpseAggregate::query()->create([
        'period' => 'daily',
        'date' => Date::now()->subDays(200)->toDateString(),
        'metric' => 'visitors',
        'value' => 10,
        'count' => 10,
        'aggregated_at' => now(),
    ]);

    resolve(PruneService::class)->prune();

    expect(GlimpseAggregate::query()->count())->toBe(1);
});

it('prunes aggregate data when a retention limit is set', function (): void {
    config(['glimpse.retention.raw' => 90, 'glimpse.retention.aggregates' => 365]);

    GlimpseAggregate::query()->create([
        'period' => 'daily',
        'date' => Date::now()->subDays(400)->toDateString(),
        'metric' => 'visitors',
        'value' => 10,
        'count' => 10,
        'aggregated_at' => now(),
    ]);

    $counts = resolve(PruneService::class)->prune();

    expect($counts['aggregates'])->toBe(1)
        ->and(GlimpseAggregate::query()->count())->toBe(0);
});
