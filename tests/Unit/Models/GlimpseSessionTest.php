<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Date;
use LaravelGlimpse\Database\Factories\GlimpseSessionFactory;
use LaravelGlimpse\Enums\Platform;
use LaravelGlimpse\Enums\ReferrerChannel;
use LaravelGlimpse\Models\GlimpseSession;

beforeEach(function (): void {
    $this->session = GlimpseSession::factory()->create();
});

it('has correct table name', function (): void {
    expect($this->session->getTable())->toBe('glimpse_sessions');
});

it('has no timestamps', function (): void {
    expect($this->session->timestamps)->toBeFalse();
});

it('casts is_bounce to boolean', function (): void {
    $session = GlimpseSession::factory()->create(['is_bounce' => 1]);

    expect($session->is_bounce)->toBeBool();
});

it('casts page_view_count to integer', function (): void {
    $session = GlimpseSession::factory()->create(['page_view_count' => '5']);

    expect($session->page_view_count)->toBeInt()
        ->and($session->page_view_count)->toBe(5);
});

it('casts duration_seconds to integer', function (): void {
    $session = GlimpseSession::factory()->create(['duration_seconds' => '120']);

    expect($session->duration_seconds)->toBeInt()
        ->and($session->duration_seconds)->toBe(120);
});

it('casts started_at to timestamp', function (): void {
    $now = Date::now();
    $session = GlimpseSession::factory()->create(['started_at' => $now]);

    expect($session->started_at)->toBeInstanceOf(Carbon::class);
});

it('casts last_seen_at to timestamp', function (): void {
    $now = Date::now();
    $session = GlimpseSession::factory()->create(['last_seen_at' => $now]);

    expect($session->last_seen_at)->toBeInstanceOf(Carbon::class);
});

it('can mass assign fillable attributes', function (): void {
    $session = new GlimpseSession([
        'session_hash' => 'hash-456',
        'ip_hash' => 'ip-hash-456',
        'country_code' => 'DE',
    ]);

    expect($session->session_hash)->toBe('hash-456')
        ->and($session->ip_hash)->toBe('ip-hash-456')
        ->and($session->country_code)->toBe('DE');
});

it('uses GlimpseSessionFactory', function (): void {
    expect(GlimpseSession::factory())->toBeInstanceOf(GlimpseSessionFactory::class);
});

it('has page views relationship', function (): void {
    expect($this->session->pageViews())->toBeInstanceOf(HasMany::class)
        ->and($this->session->pageViews()->getRelated()->getTable())->toBe('glimpse_page_views');
});

it('has events relationship', function (): void {
    expect($this->session->events())->toBeInstanceOf(HasMany::class)
        ->and($this->session->events()->getRelated()->getTable())->toBe('glimpse_events');
});

it('casts platform to Platform enum', function (): void {
    $session = GlimpseSession::factory()->create(['platform' => 'desktop']);

    expect($session->platform)->toBeInstanceOf(Platform::class);
});

it('casts referrer_channel to ReferrerChannel enum', function (): void {
    $session = GlimpseSession::factory()->create(['referrer_channel' => 'organic']);

    expect($session->referrer_channel)->toBeInstanceOf(ReferrerChannel::class);
});

it('records subsequent hit correctly', function (): void {
    $session = GlimpseSession::factory()->create([
        'page_view_count' => 1,
        'is_bounce' => true,
        'duration_seconds' => 0,
    ]);

    $now = Date::now();
    $session->recordSubsequentHit('/new-page', $now);

    expect($session->exit_page)->toBe('/new-page')
        ->and($session->page_view_count)->toBeGreaterThan(1)
        ->and($session->is_bounce)->toBeFalse();
});
