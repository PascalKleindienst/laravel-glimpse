<?php

declare(strict_types=1);

namespace LaravelGlimpse\Filament\Widgets;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use LaravelGlimpse\Contracts\QueryServiceContract;
use LaravelGlimpse\Enums\Platform;
use LaravelGlimpse\Filament\Concerns\HasDateRange;
use LaravelGlimpse\Filament\Concerns\HasTabs;
use Override;

final class DevicesWidget extends TableWidget
{
    use HasDateRange;
    use HasTabs;

    /**
     * @return array<string, string>
     */
    public function getTabs(): array
    {
        return [
            'platforms' => 'Platforms',
            'browsers' => 'Browsers',
            'os' => 'OS',
        ];
    }

    #[Override]
    public function getColumnSpan(): int|string|array
    {
        return config('glimpse.widget.devices.columns', 1);
    }

    #[Override]
    public function table(Table $table): Table
    {
        $dateRange = $this->getDateRange();
        $query = resolve(QueryServiceContract::class);
        $data = [
            'browsers' => $query->topBrowsers($dateRange, config('glimpse.widget.devices.limit', 5)),
            'platforms' => $query->platformBreakdown($dateRange),
            'os' => $query->topOs($dateRange, config('glimpse.widget.devices.limit', 5)),
        ];

        return $this->setupTabs($table, $data, function (TextColumn $column): void {
            if ($column->getName() === 'platform') {
                $column->formatStateUsing(fn (Platform $state): string => $state->icon().' '.$state->name);
            }
        });
    }

    #[Override]
    protected function getTableHeading(): string
    {
        return __('Devices');
    }
}
