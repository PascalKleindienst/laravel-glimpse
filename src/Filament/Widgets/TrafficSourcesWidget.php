<?php

declare(strict_types=1);

namespace LaravelGlimpse\Filament\Widgets;

use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use LaravelGlimpse\Contracts\QueryServiceContract;
use LaravelGlimpse\Filament\Concerns\HasDateRange;
use LaravelGlimpse\Filament\Concerns\HasTabs;
use Override;

final class TrafficSourcesWidget extends TableWidget
{
    use HasDateRange;
    use HasTabs;

    /**
     * @return array<string, string>
     */
    public function getTabs(): array
    {
        return [
            'channels' => __('glimpse::messages.tabs.channels'),
            'referrers' => __('glimpse::messages.tabs.referrers'),
        ];
    }

    #[Override]
    public function getColumnSpan(): int|string|array
    {
        return config('glimpse.widget.traffic_sources.columns', 1);
    }

    #[Override]
    public function table(Table $table): Table
    {
        $dateRange = $this->getDateRange();
        $query = resolve(QueryServiceContract::class);
        $data = [
            'referrers' => $query->topReferrers($dateRange),
            'channels' => $query->topChannels($dateRange),
        ];

        return $this->setupTabs($table, $data);
    }

    #[Override]
    protected function getTableHeading(): string
    {
        return __('glimpse::messages.cards.traffic_sources');
    }
}
