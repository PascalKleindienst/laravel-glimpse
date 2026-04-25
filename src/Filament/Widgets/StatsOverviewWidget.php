<?php

declare(strict_types=1);

namespace LaravelGlimpse\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Number;
use LaravelGlimpse\Contracts\QueryServiceContract;
use LaravelGlimpse\Filament\Concerns\HasDateRange;
use Override;

final class StatsOverviewWidget extends BaseWidget
{
    use HasDateRange;

    #[Override]
    protected function getStats(): array
    {
        $dateRange = $this->getDateRange();
        $query = resolve(QueryServiceContract::class);
        $summary = $query->summary($dateRange);
        $previous = $query->previousPeriodSummary($dateRange);
        $timeSeries = $query->timeSeries($dateRange);
        $stats = [];

        if (config('glimpse.widget.stats.show_visitors', true)) {
            $stats[] = $this->makeStat(__('glimpse::messages.cards.visitors'), $summary['visitors'], $previous['visitors'])
                ->chart($timeSeries->map(static fn (array $row): int => $row['visitors']));
        }

        if (config('glimpse.widget.stats.show_page_views', true)) {
            $stats[] = $this->makeStat(__('glimpse::messages.cards.page_views'), $summary['page_views'], $previous['page_views'])
                ->chart($timeSeries->map(static fn (array $row): int => $row['page_views']));
        }

        if (config('glimpse.widget.stats.show_bounce_rate', true)) {
            $stats[] = $this->makeStat(
                __('glimpse::messages.cards.bounce_rate'),
                $summary['bounce_rate'],
                $previous['bounce_rate'],
                format: fn (float $v): string => number_format($v, 1).'%',
                inverse: true,
            );
        }

        if (config('glimpse.widget.stats.show_avg_duration', true)) {
            $stats[] = $this->makeStat(
                __('glimpse::messages.cards.avg_duration'),
                $summary['avg_duration'],
                $previous['avg_duration'],
                format: fn (float $value): string => gmdate('i:s', (int) $value).' min',
            );
        }

        return $stats;
    }

    private function makeStat(
        string $label,
        int|float $current,
        int|float $previous,
        ?callable $format = null,
        bool $inverse = false,
    ): Stat {
        $formatted = $format
            ? $format($current)
            : number_format($current);

        $stat = Stat::make($label, $formatted);

        $trend = $this->calculateTrend($current, $previous, $inverse);

        if ($trend !== null) {
            $stat->description(__($trend >= 0 ? 'glimpse::messages.increase' : 'glimpse::messages.decrease', ['num' => Number::percentage($trend, 2)]))
                ->descriptionIcon($trend >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($trend >= 0 ? 'success' : 'danger');
        }

        return $stat;
    }

    private function calculateTrend(int|float $current, int|float $previous, bool $inverse = false): ?float
    {
        if ($current === 0 && $previous === 0) {
            return null;
        }

        if ($previous === 0 || $previous <= PHP_FLOAT_EPSILON) {
            return $current > 0 ? 100 : -100;
        }

        $change = (($current - $previous) / $previous) * 100;

        return round($inverse ? -$change : $change, 2);
    }
}
