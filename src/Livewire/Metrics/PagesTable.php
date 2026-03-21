<?php

declare(strict_types=1);

namespace LaravelGlimpse\Livewire\Metrics;

use Illuminate\Contracts\View\View;
use LaravelGlimpse\Livewire\Concerns\IsCard;
use Livewire\Component;

final class PagesTable extends Component
{
    use IsCard;

    public function render(): View
    {
        $pages = $this->query->topPages($this->dateRange, limit: 20);
        $max = $pages->max('views') ?: 1;

        return view('glimpse::livewire.metrics.pages-table', [
            'pages' => $pages,
            'max' => $max,
        ]);
    }
}
