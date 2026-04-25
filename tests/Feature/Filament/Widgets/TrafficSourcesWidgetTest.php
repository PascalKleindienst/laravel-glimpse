<?php

declare(strict_types=1);

use LaravelGlimpse\Filament\Widgets\TrafficSourcesWidget;
use Livewire\Livewire;

it('returns correct tabs', function (): void {
    $widget = new TrafficSourcesWidget();

    expect($widget->getTabs())->toBe([
        'channels' => 'Channels',
        'referrers' => 'Referrers',
    ]);
});

it('returns correct column span from config', function (): void {
    config()->set('glimpse.widget.traffic_sources.columns', 2);

    $widget = new TrafficSourcesWidget();

    expect($widget->getColumnSpan())->toBe(2);
});

it('defaults to full width when config returns null', function (): void {
    $widget = new TrafficSourcesWidget();

    expect($widget->getColumnSpan())->toBe(1);
});

it('renders table with data from query service', function (): void {
    seedAggregate('visitors', 'channel:organic', 0, 600, period: 'daily');
    seedAggregate('visitors', 'channel:direct', 0, 350, period: 'daily');
    seedAggregate('visitors', 'channel:social', 0, 50, period: 'daily');
    seedAggregate('visitors', 'referrer:google.com', 0, 500, period: 'daily');
    seedAggregate('visitors', 'referrer:twitter.com', 0, 200, period: 'daily');

    $widget = Livewire::test(TrafficSourcesWidget::class)
        ->assertSuccessful();

    $widget->assertTableColumnVisible('channel')
        ->assertTableColumnVisible('visitors')
        ->assertTableColumnVisible('percentage')
        ->assertTableColumnHidden('domain');

    $widget->filterTable('source', 'referrers')
        ->assertTableColumnVisible('domain')
        ->assertTableColumnHidden('channel');
});
