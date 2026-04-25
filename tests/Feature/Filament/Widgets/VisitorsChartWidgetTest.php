<?php

declare(strict_types=1);

use LaravelGlimpse\Filament\Widgets\VisitorsChartWidget;

it('returns correct column span from config', function (): void {
    config()->set('glimpse.widget.visitors.columns', 2);

    $widget = new VisitorsChartWidget();

    expect($widget->getColumnSpan())->toBe(2);
});

it('defaults to full when config is not set', function (): void {
    $widget = new VisitorsChartWidget();

    expect($widget->getColumnSpan())->toBe('full');
});

it('returns correct heading', function (): void {
    $widget = new VisitorsChartWidget();

    expect($widget->getHeading())->toBe('Visitors over time');
});

it('renders chart with data from query service', function (): void {
    config()->set('glimpse.widget.default_range', 'today');
    config()->set('glimpse.widget.visitors.show_visitors', true);
    config()->set('glimpse.widget.visitors.show_page_views', true);

    seedAggregate('visitors', null, 0, 600, period: 'daily');
    seedAggregate('page_views', null, 0, 800, period: 'daily');

    Livewire\Livewire::test(VisitorsChartWidget::class)
        ->assertSuccessful();
});

it('renders chart without visitors when disabled', function (): void {
    config()->set('glimpse.widget.default_range', 'today');
    config()->set('glimpse.widget.visitors.show_visitors', false);
    config()->set('glimpse.widget.visitors.show_page_views', true);

    seedAggregate('visitors', null, 0, 600, period: 'daily');
    seedAggregate('page_views', null, 0, 800, period: 'daily');

    Livewire\Livewire::test(VisitorsChartWidget::class)
        ->assertSuccessful();
});

it('renders chart without page views when disabled', function (): void {
    config()->set('glimpse.widget.default_range', 'today');
    config()->set('glimpse.widget.visitors.show_visitors', true);
    config()->set('glimpse.widget.visitors.show_page_views', false);

    seedAggregate('visitors', null, 0, 600, period: 'daily');
    seedAggregate('page_views', null, 0, 800, period: 'daily');

    Livewire\Livewire::test(VisitorsChartWidget::class)
        ->assertSuccessful();
});
