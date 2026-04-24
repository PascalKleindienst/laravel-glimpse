<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use LaravelGlimpse\Enums\Platform;
use LaravelGlimpse\Enums\ReferrerChannel;
use LaravelGlimpse\Facades\SessionTrackerService;
use LaravelGlimpse\Models\GlimpsePageView;
use LaravelGlimpse\Models\GlimpseSession;

beforeEach(function (): void {
    config(['glimpse.session_timeout' => 30]);
});

it('resolves session hash from request session id', function (): void {
    $request = Request::create('/test');
    $request->setLaravelSession(session()->driver('array'));

    $hash = SessionTrackerService::resolveSessionHash($request);

    expect($hash)->toBe(hash('sha256', $request->session()->getId()));
});

it('hashes IP address with app key', function (): void {
    config(['app.key' => 'base64:abc123']);

    $hash = SessionTrackerService::hashIp('192.168.1.1');

    expect($hash)->toBe(hash('sha256', '192.168.1.1base64:abc123'));
});

it('returns null when cached session does not exist', function (): void {
    $result = SessionTrackerService::getCachedSession('nonexistent-hash');

    expect($result)->toBeNull();
});

it('returns cached session when it exists', function (): void {
    $session = GlimpseSession::factory()->create();
    Cache::put("glimpse:session:{$session->session_hash}", $session->id);

    $result = SessionTrackerService::getCachedSession($session->session_hash);

    expect($result)->not->toBeNull()
        ->and($result->id)->toBe($session->id);
});

it('creates session and caches it', function (): void {
    $now = Date::now();
    $attributes = [
        'session_hash' => 'new-session-hash',
        'ip_hash' => 'ip-hash-123',
        'country_code' => 'US',
        'browser' => 'Chrome',
        'platform' => Platform::Desktop,
        'referrer_channel' => ReferrerChannel::Direct,
        'entry_page' => '/home',
        'page_view_count' => 1,
        'is_bounce' => true,
        'started_at' => $now,
        'last_seen_at' => $now,
    ];

    $session = SessionTrackerService::createSession($attributes);

    expect($session->session_hash)->toBe('new-session-hash')
        ->and($session->ip_hash)->toBe('ip-hash-123')
        ->and(Cache::has('glimpse:session:new-session-hash'))->toBeTrue();
});

it('updates session counters and refreshes cache', function (): void {
    $session = GlimpseSession::factory()->create([
        'page_view_count' => 1,
        'is_bounce' => true,
        'started_at' => Date::now()->subMinutes(5),
        'last_seen_at' => Date::now()->subMinutes(5),
    ]);

    GlimpsePageView::query()->create([
        'session_hash' => $session->session_hash,
        'path' => '/first-page',
        'url' => 'http://test.com/first-page',
    ]);

    $now = Date::now();
    SessionTrackerService::updateSession($session, '/new-page', $now);

    expect($session->exit_page)->toBe('/new-page')
        ->and($session->page_view_count)->toBe(2)
        ->and($session->is_bounce)->toBeFalse();
});

it('records page view with correct attributes', function (): void {
    $request = Request::create('/test?foo=bar');
    $request->headers->set('referer', 'https://google.com');

    $pageView = SessionTrackerService::recordPageView('session-hash-123', $request, Date::now());

    expect($pageView->session_hash)->toBe('session-hash-123')
        ->and($pageView->url)->toContain('/test')
        ->and($pageView->path)->toBe('/test')
        ->and($pageView->query_string)->toBe('foo=bar')
        ->and($pageView->referrer)->toBe('https://google.com');
});

it('records page view with root path', function (): void {
    $request = Request::create('/');

    $pageView = SessionTrackerService::recordPageView('session-hash', $request, Date::now());

    expect($pageView->path)->toBe('/');
});

it('checks if session is active in cache', function (): void {
    $session = GlimpseSession::factory()->create();
    Cache::put("glimpse:session:{$session->session_hash}", $session->id, 10);

    expect(SessionTrackerService::isActive($session->session_hash))->toBeTrue();
});

it('returns false when session is not active', function (): void {
    expect(SessionTrackerService::isActive('nonexistent-hash'))->toBeFalse();
});

it('session timeout is configurable', function (): void {
    config(['glimpse.session_timeout' => 60]);

    $now = Date::now();
    $attributes = [
        'session_hash' => 'timeout-test-hash',
        'page_view_count' => 1,
        'started_at' => $now,
        'last_seen_at' => $now,
    ];

    SessionTrackerService::createSession($attributes);

    expect(Cache::get('glimpse:session:timeout-test-hash'))->not->toBeNull();
});
