<?php

declare(strict_types=1);

use LaravelGlimpse\Filament\Widgets\DevicesWidget;
use Livewire\Livewire;

it('returns correct tabs', function (): void {
    $widget = new DevicesWidget();

    expect($widget->getTabs())->toBe([
        'platforms' => __('glimpse::messages.tabs.platforms'),
        'browsers' => __('glimpse::messages.tabs.browsers'),
        'os' => __('glimpse::messages.tabs.os'),
    ]);
});

it('returns correct column span from config', function (): void {
    config()->set('glimpse.widget.devices.columns', 2);

    $widget = new DevicesWidget();

    expect($widget->getColumnSpan())->toBe(2);
});

it('defaults to full width when config returns null', function (): void {
    $widget = new DevicesWidget();

    expect($widget->getColumnSpan())->toBe(1);
});

it('renders table with data from query service', function (): void {
    seedAggregate('visitors', 'platform:desktop', 0, 600, period: 'daily');
    seedAggregate('visitors', 'platform:mobile', 0, 350, period: 'daily');
    seedAggregate('visitors', 'platform:tablet', 0, 50, period: 'daily');
    seedAggregate('visitors', 'browser:Chrome', 0, 700, period: 'daily');
    seedAggregate('visitors', 'browser:Firefox', 0, 200, period: 'daily');
    seedAggregate('visitors', 'os:Windows', 0, 500, period: 'daily');

    $widget = Livewire::test(DevicesWidget::class)
        ->assertSuccessful()
        ->assertTableColumnVisible('platform')
        ->assertTableColumnVisible('visitors')
        ->assertTableColumnVisible('percentage')
        ->assertTableColumnHidden('browser')
        ->assertTableColumnHidden('os');

    $widget->filterTable('source', 'browsers')
        ->assertTableColumnVisible('browser')
        ->assertTableColumnHidden('platform')
        ->assertTableColumnHidden('os');

    $widget->filterTable('source', 'os')
        ->assertTableColumnVisible('os')
        ->assertTableColumnHidden('browser')
        ->assertTableColumnHidden('platform');
});
