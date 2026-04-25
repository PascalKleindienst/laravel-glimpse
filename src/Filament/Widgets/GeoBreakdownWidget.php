<?php

declare(strict_types=1);

namespace LaravelGlimpse\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use LaravelGlimpse\Contracts\QueryServiceContract;
use LaravelGlimpse\Filament\Concerns\HasDateRange;
use LaravelGlimpse\Filament\Concerns\HasTabs;
use LaravelGlimpse\Values\Country;
use Override;

final class GeoBreakdownWidget extends TableWidget
{
    use HasDateRange;
    use HasTabs;

    /**
     * @return array<string, string>
     */
    public function getTabs(): array
    {
        return [
            'countries' => 'Countries',
            'cities' => 'Cities',
            'languages' => 'Languages',
        ];
    }

    #[Override]
    public function getColumnSpan(): int|string|array
    {
        return config('glimpse.widget.geo_breakdown.columns', 1);
    }

    #[Override]
    public function table(Table $table): Table
    {
        $dateRange = $this->getDateRange();
        $query = resolve(QueryServiceContract::class);
        $data = [
            'countries' => $query->topCountries($dateRange, config('glimpse.widget.geo_breakdown.limit', 5)),
            'cities' => $query->topCities($dateRange, config('glimpse.widget.geo_breakdown.limit', 5)),
            'languages' => $query->topLanguages($dateRange, config('glimpse.widget.geo_breakdown.limit', 5)),
        ];

        return $this->setupTabs($table, $data, function (TextColumn $column): void {
            if ($column->getName() === 'country') {
                $column->formatStateUsing(fn (Country $state): string => $state->flag.' '.$state->name);
            }
        });
    }

    #[Override]
    protected function getTableHeading(): string
    {
        return __('Geographic Breakdown');
    }
}
