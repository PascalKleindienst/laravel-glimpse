<?php

declare(strict_types=1);

use LaravelGlimpse\Filament\Widgets\GeoBreakdownWidget;
use Livewire\Livewire;

it('returns correct tabs', function (): void {
    $widget = new GeoBreakdownWidget();

    expect($widget->getTabs())->toBe([
        'countries' => __('glimpse::messages.tabs.countries'),
        'cities' => __('glimpse::messages.tabs.cities'),
        'languages' => __('glimpse::messages.tabs.languages'),
    ]);
});

it('returns correct column span from config', function (): void {
    config()->set('glimpse.widget.geo_breakdown.columns', 2);

    $widget = new GeoBreakdownWidget();

    expect($widget->getColumnSpan())->toBe(2);
});

it('defaults to full width when config returns null', function (): void {
    $widget = new GeoBreakdownWidget();

    expect($widget->getColumnSpan())->toBe(1);
});

it('renders table with data from query service', function (): void {
    seedAggregate('visitors', 'country:US', 0, 600, period: 'daily');
    seedAggregate('visitors', 'country:DE', 0, 350, period: 'daily');
    seedAggregate('visitors', 'country:FR', 0, 50, period: 'daily');
    seedAggregate('visitors', 'city:Berlin', 0, 300, period: 'daily');
    seedAggregate('visitors', 'city:Paris', 0, 200, period: 'daily');
    seedAggregate('visitors', 'language:en', 0, 500, period: 'daily');
    seedAggregate('visitors', 'language:de', 0, 300, period: 'daily');

    $widget = Livewire::test(GeoBreakdownWidget::class)
        ->assertSuccessful()
        ->assertTableColumnVisible('country')
        ->assertTableColumnVisible('visitors')
        ->assertTableColumnVisible('percentage')
        ->assertTableColumnHidden('city')
        ->assertTableColumnHidden('language');

    $widget->filterTable('source', 'cities')
        ->assertTableColumnVisible('city')
        ->assertTableColumnHidden('country')
        ->assertTableColumnHidden('language');

    $widget->filterTable('source', 'languages')
        ->assertTableColumnVisible('language')
        ->assertTableColumnHidden('country')
        ->assertTableColumnHidden('city');
});
