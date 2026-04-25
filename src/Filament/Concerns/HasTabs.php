<?php

declare(strict_types=1);

namespace LaravelGlimpse\Filament\Concerns;

use Closure;
use Filament\Tables\Columns\Column;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;
use Illuminate\Support\Str;

trait HasTabs
{
    protected string $activeTab;

    public function bootHasTabs(): void
    {
        $this->activeTab = (string) Arr::first(array_keys($this->getTabs()), default: '');
    }

    public function updated(string $name, mixed $value): void
    {
        if (Str::of($name)->contains('source.value')) {
            $this->activeTab = $value;
        }

        if ($name === 'tableFilters.source') {
            $this->activeTab = (string) $value['value'];
        }
    }

    /**
     * @param  array<string, Collection<array-key, mixed>>  $data
     * @param  (Closure(TextColumn): void)|null  $formatColumnUsing
     */
    private function setupTabs(Table $table, array $data = [], ?Closure $formatColumnUsing = null): Table
    {
        $columns = collect();

        foreach ($this->getTabs() as $tab => $_) {
            collect(array_keys($data[$tab]->first() ?? []))
                ->filter(fn (string|int $column): bool => $column !== 'visitors' && $column !== 'percentage')
                ->map(fn (string|int $column): TextColumn => TextColumn::make((string) $column)
                    ->label(__('glimpse.tabs.'.$column))
                    ->hidden(fn (): bool => $this->activeTab !== $tab)
                )
                ->each(fn (TextColumn $column) => $formatColumnUsing ? $formatColumnUsing($column) : $column)
                ->each(fn (TextColumn $column) => $columns->push($column));
        }

        return $table
            ->records(fn () => $data[$this->activeTab])
            ->hiddenFilterIndicators()
            ->filtersFormColumns(1)
            ->persistFiltersInSession()
            ->deferFilters(false)
            ->filters([
                SelectFilter::make('source')
                    ->options($this->getTabs())
                    ->default($this->activeTab)
                    ->selectablePlaceholder(false),
            ], layout: FiltersLayout::AboveContent)
            ->columns([
                ...$columns->all(),
                $this->makeVisitorsColumn(),
                $this->makePercentageColumn($data),
            ]);
    }

    private function makeVisitorsColumn(): Column
    {
        return TextColumn::make('visitors')
            ->label(__('visitors'))
            ->numeric();
    }

    /**
     * @param  array<string, Collection<array-key, mixed>>  $data
     */
    private function makePercentageColumn(array $data): Column
    {
        return TextColumn::make('percentage')
            ->label(__('Percentage'))
            ->state(fn (/** @var array{visitors: int} $record */ array $record) => Number::percentage(
                $record['visitors'] / $data[$this->activeTab]->max('visitors') * 100,
                2,
                2
            ));
    }
}
