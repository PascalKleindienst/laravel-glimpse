<?php

declare(strict_types=1);

namespace LaravelGlimpse\Livewire\Metrics;

use Illuminate\Contracts\View\View;
use LaravelGlimpse\Livewire\Concerns\IsCard;
use Livewire\Component;

final class EventsTable extends Component
{
    use IsCard;

    public function render(): View
    {
        $events = $this->query->topEvents($this->dateRange, limit: 20);
        $max = $events->max('count') ?: 1;

        return view('glimpse::livewire.metrics.events-table', [
            'events' => $events,
            'max' => $max,
        ]);
    }
}
