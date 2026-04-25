<?php

declare(strict_types=1);

namespace LaravelGlimpse\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use LaravelGlimpse\Contracts\QueryServiceContract;
use LaravelGlimpse\Filament\Concerns\HasDateRange;
use Override;

final class VisitorsChartWidget extends ChartWidget
{
    use HasDateRange;

    protected ?string $maxHeight = '300px';

    #[Override]
    public function getColumnSpan(): int|string|array
    {
        return config('glimpse.widget.visitors.columns', 'full');
    }

    #[Override]
    public function getHeading(): string
    {
        return __('Visitors over time');
    }

    #[Override]
    protected function getData(): array
    {
        $dateRange = $this->getDateRange();
        $series = resolve(QueryServiceContract::class)->timeSeries($dateRange);

        $datasets = [];

        if (config('glimpse.widget.visitors.show_visitors', true)) {
            $datasets[] = [
                'label' => 'Visitors',
                'data' => $series->pluck('visitors')->values()->toArray(),
                'borderColor' => '#6366f1',
                'backgroundColor' => 'rgba(99, 102, 241, 0.1)',
                'fill' => true,
                'tension' => 0.4,
            ];
        }

        if (config('glimpse.widget.visitors.show_page_views', true)) {
            $datasets[] = [
                'label' => 'Page Views',
                'data' => $series->pluck('page_views')->values()->toArray(),
                'borderColor' => '#22c55e',
                'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                'fill' => true,
                'tension' => 0.4,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $series->pluck('label')->values()->toArray(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
