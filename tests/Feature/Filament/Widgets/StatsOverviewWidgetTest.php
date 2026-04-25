<?php

declare(strict_types=1);

use LaravelGlimpse\Filament\Widgets\StatsOverviewWidget;
use Livewire\Livewire;

it('renders with only visitors enabled and all data present', function (string $scenario): void {
    config()->set('glimpse.widget.stats.show_visitors', $scenario === 'visitors');
    config()->set('glimpse.widget.stats.show_page_views', $scenario === 'page_views');
    config()->set('glimpse.widget.stats.show_bounce_rate', $scenario === 'bounce_rate');
    config()->set('glimpse.widget.stats.show_avg_duration', $scenario === 'avg_duration');

    $today = today();
    for ($i = 0; $i < 30; $i++) {
        $date = $today->copy()->subDays($i);
        seedAggregate('visitors', null, 0, 600, period: 'daily', date: $date->toDateString());
    }

    for ($i = 30; $i < 60; $i++) {
        $date = $today->copy()->subDays($i);
        seedAggregate('visitors', null, 0, 500, period: 'daily', date: $date->toDateString());
    }

    $see = match ($scenario) {
        'visitors' => 'Visitors',
        'page_views' => 'Page Views',
        'bounce_rate' => 'Bounce Rate',
        'avg_duration' => 'Avg. Duration'
    };

    $dontSee = match ($scenario) {
        'visitors' => ['Page Views', 'Bounce Rate', 'Avg. Duration'],
        'page_views' => ['Visitors', 'Bounce Rate', 'Avg. Duration'],
        'bounce_rate' => ['Visitors', 'Page Views', 'Avg. Duration'],
        'avg_duration' => ['Visitors', 'Page Views', 'Bounce Rate'],
    };

    Livewire::test(StatsOverviewWidget::class)->assertSuccessful()
        ->assertSee($see)
        ->assertDontSee($dontSee);
})->with([
    'visitors', 'page_views', 'bounce_rate', 'avg_duration',
]);
