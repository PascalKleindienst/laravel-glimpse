<?php

declare(strict_types=1);

use LaravelGlimpse\Filament\Widgets\PagesTableWidget;
use Livewire\Livewire;

it('returns correct column span from config', function (): void {
    config()->set('glimpse.widget.pages.columns', 1);

    $widget = new PagesTableWidget();

    expect($widget->getColumnSpan())->toBe(1);
});

it('defaults to 2 when config is not set', function (): void {
    $widget = new PagesTableWidget();

    expect($widget->getColumnSpan())->toBe(2);
});

it('renders table with data from query service', function (): void {
    seedAggregate('page_views', 'path:/home', 0, 600, period: 'daily');
    seedAggregate('page_views', 'path:/pricing', 0, 350, period: 'daily');
    seedAggregate('page_views', 'path:/about', 0, 50, period: 'daily');

    Livewire::test(PagesTableWidget::class)
        ->assertSuccessful()
        ->assertTableColumnExists('path')
        ->assertTableColumnExists('views')
        ->assertTableColumnExists('percentage');
});

it('renders with empty data when no aggregates exist', function (): void {
    Livewire::test(PagesTableWidget::class)
        ->assertSuccessful()
        ->assertTableColumnExists('path')
        ->assertTableColumnExists('views')
        ->assertTableColumnExists('percentage');
});
