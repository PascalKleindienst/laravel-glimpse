<?php

declare(strict_types=1);

namespace LaravelGlimpse\Livewire;

use Illuminate\Contracts\View\View;
use LaravelGlimpse\Contracts\QueryServiceContract;
use LaravelGlimpse\Values\DateRange;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Url;
use Livewire\Component;

/**
 * @property-read DateRange $dateRange
 * @property-read array{ visitors: int, page_views: int, sessions: int, bounce_rate: float, avg_duration: float } $summary
 * @property-read array{ visitors: int, page_views: int, sessions: int, bounce_rate: float, avg_duration: float } $previousSummary
 */
final class Dashboard extends Component
{
    /** The active preset or 'custom'. Synced to URL query string. */
    #[Url(as: 'range')]
    public string $preset = '7d';

    public function setPreset(string $preset): void
    {
        $this->preset = $preset;
    }

    #[Computed]
    public function dateRange(): DateRange
    {
        return DateRange::fromPreset($this->preset);
    }

    /**
     * @return array{
     *    visitors: int,
     *    page_views: int,
     *    sessions: int,
     *    bounce_rate: float,
     *    avg_duration: float,
     *  }
     */
    #[Computed]
    public function summary(): array
    {
        return app(QueryServiceContract::class)->summary($this->dateRange);
    }

    /**
     * @return array{
     *    visitors: int,
     *    page_views: int,
     *    sessions: int,
     *    bounce_rate: float,
     *    avg_duration: float,
     *  }
     */
    #[Computed]
    public function previousSummary(): array
    {
        return app(QueryServiceContract::class)->previousPeriodSummary($this->dateRange);
    }

    public function render(): View
    {
        return view('glimpse::livewire.dashboard')->layout('glimpse::layouts.glimpse');
    }
}
