<?php

declare(strict_types=1);

namespace LaravelGlimpse\Livewire\Metrics;

use Illuminate\Contracts\View\View;
use LaravelGlimpse\Livewire\Concerns\IsCard;
use Livewire\Component;

final class DevicesBreakdown extends Component
{
    use IsCard;

    /** 'platforms' | 'browsers' | 'os' */
    public string $tab = 'platforms';

    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function render(): View
    {
        $platforms = $this->query->platformBreakdown($this->dateRange);
        $browsers = $this->query->topBrowsers($this->dateRange);
        $os = $this->query->topOs($this->dateRange);

        $browserMax = $browsers->max('visitors') ?: 1;
        $osMax = $os->max('visitors') ?: 1;

        return view('glimpse::livewire.metrics.devices-breakdown', [
            'tab' => $this->tab,
            'platforms' => $platforms,
            'browsers' => $browsers,
            'os' => $os,
            'browserMax' => $browserMax,
            'osMax' => $osMax,
        ]);
    }
}
