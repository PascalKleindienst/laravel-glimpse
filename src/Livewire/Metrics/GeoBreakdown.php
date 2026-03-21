<?php

declare(strict_types=1);

namespace LaravelGlimpse\Livewire\Metrics;

use Illuminate\Contracts\View\View;
use LaravelGlimpse\Livewire\Concerns\IsCard;
use Livewire\Component;

final class GeoBreakdown extends Component
{
    use IsCard;

    /**
     * @var 'countries' | 'cities' | 'languages'
     */
    public string $tab = 'countries';

    /**
     * @param  'countries' | 'cities' | 'languages'  $tab
     */
    public function setTab(string $tab): void
    {
        $this->tab = $tab;
    }

    public function render(): View
    {
        $countries = $this->query->topCountries($this->dateRange, 15);
        $cities = $this->query->topCities($this->dateRange, 15);
        $languages = $this->query->topLanguages($this->dateRange, 10);

        $countryMax = $countries->max('visitors') ?: 1;
        $cityMax = $cities->max('visitors') ?: 1;
        $languageMax = $languages->max('visitors') ?: 1;

        return view('glimpse::livewire.metrics.geo-breakdown', [
            'tab' => $this->tab,
            'countries' => $countries,
            'cities' => $cities,
            'languages' => $languages,
            'countryMax' => $countryMax,
            'cityMax' => $cityMax,
            'languageMax' => $languageMax,
        ]);
    }
}
