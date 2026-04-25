<?php

declare(strict_types=1);

namespace LaravelGlimpse\Filament\Widgets;

use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Support\Number;
use LaravelGlimpse\Contracts\QueryServiceContract;
use LaravelGlimpse\Filament\Concerns\HasDateRange;
use Override;

final class PagesTableWidget extends TableWidget
{
    use HasDateRange;

    #[Override]
    public function getColumnSpan(): int|string|array
    {
        return config('glimpse.widget.pages.columns');
    }

    #[Override]
    public function table(Table $table): Table
    {
        $dateRange = $this->getDateRange();
        $pages = resolve(QueryServiceContract::class)->topPages($dateRange, config('glimpse.widget.pages.limit', 10));
        $max = $pages->max('views') ?: 1;

        return $table
            ->records(static fn () => $pages)
            ->recordActions([
                Action::make('open')
                    ->label(__('glimpse::messages.open'))
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    // @phpstan-ignore-next-line
                    ->url(static fn (/** @var array{path: string} $record */ array $record): string => url($record['path']))
                    ->openUrlInNewTab(),
            ])
            ->columns([
                TextColumn::make('path')->label(__('glimpse::messages.columns.path')),
                TextColumn::make('views')->label(__('glimpse::messages.columns.views'))->numeric(),
                TextColumn::make('percentage')->label(__('glimpse::messages.columns.percentage'))
                    ->state(fn (/** @var array{views: int} $record */ array $record): string|false => Number::percentage(
                        $record['views'] / $max * 100,
                        2,
                        2
                    )),
            ]);
    }

    #[Override]
    protected function getTableHeading(): string
    {
        return __('glimpse::messages.cards.top_pages');
    }
}
