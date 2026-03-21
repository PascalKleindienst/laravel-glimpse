<?php

declare(strict_types=1);

namespace LaravelGlimpse\Livewire\Metrics;

use Illuminate\Contracts\View\View;
use LaravelGlimpse\Livewire\Concerns\IsCard;
use Livewire\Component;

final class VisitorsChart extends Component
{
    use IsCard;

    public function render(): View
    {
        $series = $this->query->timeSeries($this->dateRange);

        return view('glimpse::livewire.metrics.visitors-chart', [
            'labels' => $series->pluck('label')->values()->toArray(),
            'visitors' => $series->pluck('visitors')->values()->toArray(),
            'page_views' => $series->pluck('page_views')->values()->toArray(),
        ]);
    }
}
