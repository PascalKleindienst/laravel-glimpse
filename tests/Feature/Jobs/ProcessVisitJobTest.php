<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;
use LaravelGlimpse\Data\VisitData;
use LaravelGlimpse\Jobs\ProcessVisitJob;
use LaravelGlimpse\Models\GlimpsePageView;
use LaravelGlimpse\Models\GlimpseSession;
use LaravelGlimpse\Services\SessionTrackerService;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

beforeEach(function (): void {
    config(['glimpse.session_timeout' => 30]);
    config(['app.key' => 'base64:abcdefghijklmnopqrstuvwxyz123456']);
});

/**
 * @param  array{
 *      sessionHash?: string,
 *      ipHash?: string,
 *      fullUrl?: string,
 *      path?: string,
 *      queryString?: string,
 *      userAgent?: string,
 *      referer?: string,
 *       acceptLanguage?: string,
 *       ip?: string,
 *        hitAt?: CarbonImmutable,
 *        isNewSession?: bool,
 * }  $overwrite
 */
function createVisitData(array $overwrite = []): VisitData
{
    return new VisitData(
        sessionHash: $overwrite['sessionHash'] ?? 'new-session-hash-123',
        ipHash: $overwrite['ipHash'] ?? 'ip-hash-abc',
        fullUrl: $overwrite['fullUrl'] ?? 'http://example.com/home',
        path: $overwrite['path'] ?? '/home',
        queryString: $overwrite['queryString'] ?? null,
        userAgent: $overwrite['userAgent'] ?? 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0',
        referer: $overwrite['referer'] ?? null,
        acceptLanguage: $overwrite['acceptLanguage'] ?? 'en-US,en;q=0.9',
        ip: $overwrite['ip'] ?? '192.168.1.1',
        hitAt: $overwrite['hitAt'] ?? CarbonImmutable::now(),
        isNewSession: $overwrite['isNewSession'] ?? true,
    );
}

it('creates new session and page view for new visitor', function (): void {
    $job = new ProcessVisitJob(createVisitData());
    $job->handle(resolve(SessionTrackerService::class));

    assertDatabaseHas('glimpse_sessions', [
        'session_hash' => 'new-session-hash-123',
        'ip_hash' => 'ip-hash-abc',
        'entry_page' => '/home',
        'exit_page' => '/home',
        'page_view_count' => 1,
        'is_bounce' => true,
    ]);

    assertDatabaseHas('glimpse_page_views', [
        'session_hash' => 'new-session-hash-123',
        'path' => '/home',
        'url' => 'http://example.com/home',
    ]);
});

it('updates existing session for returning visitor', function (): void {
    $session = GlimpseSession::factory()->create([
        'session_hash' => 'existing-session-hash',
        'page_view_count' => 1,
        'is_bounce' => true,
        'entry_page' => '/home',
        'exit_page' => '/home',
    ]);

    GlimpsePageView::factory()->create([
        'session_hash' => 'existing-session-hash',
        'path' => '/home',
    ]);

    Cache::put("glimpse:session:{$session->session_hash}", $session->id, now()->addMinutes(30));

    $job = new ProcessVisitJob(createVisitData([
        'sessionHash' => 'existing-session-hash',
        'ipHash' => 'ip-hash-xyz',
        'fullUrl' => 'http://example.com/about',
        'path' => '/about',
        'referer' => 'http://example.com/home',
        'isNewSession' => false,
    ]));
    $job->handle(resolve(SessionTrackerService::class));

    assertDatabaseHas('glimpse_sessions', [
        'page_view_count' => 2,
        'is_bounce' => false,
        'exit_page' => '/about',
    ]);

    $pageViews = GlimpsePageView::query()->where('session_hash', 'existing-session-hash')->get();
    expect($pageViews)->toHaveCount(2);
});

it('does not create session when session was pruned', function (): void {
    $job = new ProcessVisitJob(createVisitData([
        'sessionHash' => 'pruned-session-hash',
        'ipHash' => 'ip-hash-pruned',
        'fullUrl' => 'http://example.com/page',
        'path' => '/page',
        'isNewSession' => false,
    ]));
    $job->handle(resolve(SessionTrackerService::class));

    assertDatabaseMissing('glimpse_sessions', ['session_hash' => 'pruned-session-hash']);
    assertDatabaseMissing('glimpse_page_views', ['session_hash' => 'pruned-session-hash']);
});

it('does not record bot as new session', function (): void {
    $job = new ProcessVisitJob(createVisitData([
        'sessionHash' => 'bot-session-hash',
        'ipHash' => 'ip-hash-bot',
        'fullUrl' => 'http://example.com/',
        'path' => '/',
        'userAgent' => 'Googlebot/2.1 (+http://www.google.com/bot.html)',
        'referer' => null,
        'acceptLanguage' => null,
    ]));
    $job->handle(resolve(SessionTrackerService::class));

    assertDatabaseMissing('glimpse_sessions', ['session_hash' => 'bot-session-hash']);
    assertDatabaseMissing('glimpse_page_views', ['session_hash' => 'bot-session-hash']);
});

it('records page view with query string', function (): void {
    $job = new ProcessVisitJob(createVisitData([
        'sessionHash' => 'query-session-hash',
        'ipHash' => 'ip-hash-query',
        'fullUrl' => 'http://example.com/search?q=test',
        'path' => '/search',
        'queryString' => 'q=test',
        'userAgent' => 'Mozilla/5.0 Chrome/120.0',
    ]));
    $job->handle(resolve(SessionTrackerService::class));

    assertDatabaseHas('glimpse_page_views', ['session_hash' => 'query-session-hash', 'query_string' => 'q=test']);
});

it('records page view with referer', function (): void {
    $job = new ProcessVisitJob(createVisitData([
        'sessionHash' => 'referer-session-hash',
        'ipHash' => 'ip-hash-referer',
        'fullUrl' => 'http://example.com/page2',
        'path' => '/page2',
        'userAgent' => 'Mozilla/5.0 Chrome/120.0',
        'referer' => 'http://google.com/search',
    ]));
    $job->handle(resolve(SessionTrackerService::class));

    assertDatabaseHas('glimpse_page_views', ['session_hash' => 'referer-session-hash', 'referrer' => 'http://google.com/search']);
});

it('closes previous page view time_on_page on subsequent hit', function (): void {
    $session = GlimpseSession::factory()->create([
        'session_hash' => 'timing-session-hash',
        'page_view_count' => 1,
    ]);

    $firstPageView = GlimpsePageView::factory()->create([
        'session_hash' => 'timing-session-hash',
        'path' => '/first',
        'time_on_page_seconds' => null,
    ]);

    Cache::put("glimpse:session:{$session->session_hash}", $session->id, now()->addMinutes(30));

    $job = new ProcessVisitJob(createVisitData([
        'sessionHash' => 'timing-session-hash',
        'ipHash' => 'ip-hash-timing',
        'fullUrl' => 'http://example.com/second',
        'path' => '/second',
        'referer' => 'http://example.com/first',
        'hitAt' => CarbonImmutable::now()->addSeconds(30),
        'isNewSession' => false,
    ]));
    $job->handle(resolve(SessionTrackerService::class));

    $firstPageView->refresh();

    expect($firstPageView->time_on_page_seconds)->not->toBeNull();
});

it('creates session with resolver data', function (): void {
    $job = new ProcessVisitJob(createVisitData([
        'sessionHash' => 'resolver-session-hash',
        'ipHash' => 'ip-hash-resolver',
        'fullUrl' => 'http://example.com/test',
        'path' => '/test',
        'userAgent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 Chrome/120.0',
        'referer' => 'https://google.com/search?q=test',
    ]));
    $job->handle(resolve(SessionTrackerService::class));

    $session = GlimpseSession::query()->where('session_hash', 'resolver-session-hash')->first();

    expect($session)->not->toBeNull()
        ->and($session->browser)->not->toBeNull()
        ->and($session->platform)->not->toBeNull();
});
