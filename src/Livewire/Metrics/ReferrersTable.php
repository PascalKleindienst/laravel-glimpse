<?php

declare(strict_types=1);

namespace LaravelGlimpse\Livewire\Metrics;

use Illuminate\Contracts\View\View;
use LaravelGlimpse\Livewire\Concerns\IsCard;
use Livewire\Component;

final class ReferrersTable extends Component
{
    use IsCard;

    /**
     * @var 'channels' | 'referrers'
     */
    public string $tab = 'channels';

    /**
     * @param  'channels' | 'referrers'  $tab
     */
    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function render(): View
    {
        $channels = $this->query->topChannels($this->dateRange);
        $referrers = $this->query->topReferrers($this->dateRange);

        $channelMax = $channels->max('visitors') ?: 1;
        $referrerMax = $referrers->max('visitors') ?: 1;

        return view('glimpse::livewire.metrics.referrers-table', [
            'tab' => $this->tab,
            'channels' => $channels,
            'referrers' => $referrers,
            'channelMax' => $channelMax,
            'referrerMax' => $referrerMax,
        ]);
    }
}
