<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use LaravelGlimpse\Database\Factories\GlimpsePageViewFactory;
use LaravelGlimpse\Models\GlimpsePageView;
use LaravelGlimpse\Models\GlimpseSession;

beforeEach(function (): void {
    $this->pageView = GlimpsePageView::factory()->create();
});

it('has correct table name', function (): void {
    expect($this->pageView->getTable())->toBe('glimpse_page_views');
});

it('casts time_on_page_seconds to integer', function (): void {
    $pageView = GlimpsePageView::factory()->create(['time_on_page_seconds' => '30']);

    expect($pageView->time_on_page_seconds)->toBeInt()
        ->and($pageView->time_on_page_seconds)->toBe(30);
});

it('casts created_at to datetime', function (): void {
    $pageView = GlimpsePageView::factory()->create();

    expect($pageView->created_at)->toBeInstanceOf(Carbon::class);
});

it('uses GlimpsePageViewFactory', function (): void {
    expect(GlimpsePageView::factory())->toBeInstanceOf(GlimpsePageViewFactory::class);
});

it('has session relationship', function (): void {
    expect($this->pageView->session())->toBeInstanceOf(BelongsTo::class)
        ->and($this->pageView->session()->getRelated()->getTable())->toBe('glimpse_sessions');
});

it('can mass assign fillable attributes', function (): void {
    $session = GlimpseSession::factory()->create();

    $pageView = new GlimpsePageView([
        'session_hash' => $session->session_hash,
        'url' => 'https://example.com/page',
        'path' => '/page',
        'query_string' => 'foo=bar',
        'referrer' => 'https://example.com/referrer',
    ]);

    expect($pageView->session_hash)->toBe($session->session_hash)
        ->and($pageView->url)->toBe('https://example.com/page')
        ->and($pageView->path)->toBe('/page')
        ->and($pageView->query_string)->toBe('foo=bar')
        ->and($pageView->referrer)->toBe('https://example.com/referrer');
});

it('closes with timestamp correctly', function (): void {
    $pageView = GlimpsePageView::factory()->create([
        'created_at' => CarbonImmutable::parse('2024-01-01 10:00:00'),
        'time_on_page_seconds' => null,
    ]);

    $nextTimestamp = CarbonImmutable::parse('2024-01-01 10:02:30');
    $pageView->closeWithTimestamp($nextTimestamp);

    expect($pageView->time_on_page_seconds)->toBe(150)
        ->and($pageView->updated_at->getTimestamp())->toBe($nextTimestamp->getTimestamp());
});

it('closes with timestamp handles null created_at', function (): void {
    $pageView = GlimpsePageView::factory()->create([
        'created_at' => null,
        'time_on_page_seconds' => null,
    ]);

    $nextTimestamp = CarbonImmutable::parse('2024-01-01 10:00:00');
    $pageView->closeWithTimestamp($nextTimestamp);

    expect($pageView->time_on_page_seconds)->toBe($nextTimestamp->getTimestamp());
});
