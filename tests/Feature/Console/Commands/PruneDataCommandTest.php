<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Date;
use LaravelGlimpse\Contracts\PruneServiceContract;
use LaravelGlimpse\Models\GlimpseAggregate;
use LaravelGlimpse\Models\GlimpseEvent;
use LaravelGlimpse\Models\GlimpsePageView;
use LaravelGlimpse\Models\GlimpseSession;
use LaravelGlimpse\Services\PruneService;

use function Pest\Laravel\artisan;
use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function (): void {
    config(['glimpse.retention.raw' => 30]);
    config(['glimpse.retention.aggregates' => 90]);
    app()->bind(PruneServiceContract::class, PruneService::class);
});

it('displays pruning information messages', function (): void {
    artisan('glimpse:prune', ['--dry-run' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Pruning raw data older than 30 days')
        ->expectsOutputToContain('Pruning aggregate data older than 90 days');
});

it('displays aggregate retention message when configured', function (): void {
    config(['glimpse.retention.aggregates' => 60]);

    artisan('glimpse:prune', ['--dry-run' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Pruning aggregate data older than 60 days');
});

it('shows dry-run warning', function (): void {
    artisan('glimpse:prune', ['--dry-run' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Dry run');
});

it('counts prunable sessions in dry-run', function (): void {
    GlimpseSession::factory()->create([
        'started_at' => Date::now()->subDays(60),
    ]);

    artisan('glimpse:prune', ['--dry-run' => true])
        ->assertSuccessful()
        ->expectsOutputToContain('Sessions deleted');
});

it('deletes old sessions and page views', function (): void {
    $oldSession = GlimpseSession::factory()->create([
        'session_hash' => 'old-session-hash',
        'started_at' => Date::now()->subDays(60),
    ]);

    GlimpsePageView::factory()->create([
        'session_hash' => $oldSession->session_hash,
        'created_at' => Date::now()->subDays(60),
    ]);

    $recentSession = GlimpseSession::factory()->create([
        'session_hash' => 'recent-session-hash',
        'started_at' => Date::now()->subDays(10),
    ]);

    GlimpsePageView::factory()->create([
        'session_hash' => $recentSession->session_hash,
        'created_at' => Date::now()->subDays(10),
    ]);

    artisan('glimpse:prune')
        ->assertSuccessful()
        ->expectsOutputToContain('Pruning complete');

    assertDatabaseMissing('glimpse_sessions', ['session_hash' => 'old-session-hash']);
    assertDatabaseMissing('glimpse_page_views', ['session_hash' => 'old-session-hash']);
    assertDatabaseHas('glimpse_sessions', ['session_hash' => 'recent-session-hash']);
    assertDatabaseHas('glimpse_sessions', ['session_hash' => 'recent-session-hash']);
});

it('deletes old events', function (): void {
    GlimpseEvent::factory()->create([
        'session_hash' => 'old-event-hash',
        'created_at' => Date::now()->subDays(60),
    ]);

    GlimpseEvent::factory()->create([
        'session_hash' => 'recent-event-hash',
        'created_at' => Date::now()->subDays(10),
    ]);

    artisan('glimpse:prune')
        ->assertSuccessful();

    assertDatabaseMissing('glimpse_events', ['session_hash' => 'old-event-hash']);
    assertDatabaseHas('glimpse_events', ['session_hash' => 'recent-event-hash']);
});

it('deletes old aggregates when retention is configured', function (): void {
    config(['glimpse.retention.aggregates' => 30]);

    GlimpseAggregate::factory()->create([
        'date' => Date::now()->subDays(60),
    ]);

    GlimpseAggregate::factory()->create([
        'date' => Date::now()->subDays(10),
    ]);

    artisan('glimpse:prune')
        ->assertSuccessful();

    assertDatabaseCount('glimpse_aggregates', 1);
});

it('does not delete aggregates when retention is null', function (): void {
    config(['glimpse.retention.aggregates' => null]);

    GlimpseAggregate::factory()->create([
        'date' => Date::now()->subDays(200),
    ]);

    GlimpseAggregate::factory()->create([
        'date' => Date::now()->subDays(10),
    ]);

    artisan('glimpse:prune')
        ->assertSuccessful();

    assertDatabaseCount('glimpse_aggregates', 2);
});

it('displays deletion counts in output', function (): void {
    GlimpseSession::factory()->create([
        'started_at' => Date::now()->subDays(60),
    ]);

    GlimpsePageView::factory()->create([
        'session_hash' => 'test-hash',
        'created_at' => Date::now()->subDays(60),
    ]);

    GlimpseEvent::factory()->create([
        'session_hash' => 'test-hash',
        'created_at' => Date::now()->subDays(60),
    ]);

    artisan('glimpse:prune')
        ->assertSuccessful()
        ->expectsOutputToContain('Sessions deleted')
        ->expectsOutputToContain('Page views deleted')
        ->expectsOutputToContain('Events deleted');
});

it('returns success exit code', function (): void {
    artisan('glimpse:prune')
        ->assertSuccessful();
});

it('handles empty database gracefully', function (): void {
    artisan('glimpse:prune')
        ->assertSuccessful()
        ->expectsOutputToContain('Pruning complete');
});
