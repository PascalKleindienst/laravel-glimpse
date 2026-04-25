<?php

declare(strict_types=1);

namespace LaravelGlimpse\Filament\Concerns;

use LaravelGlimpse\Values\DateRange;

trait HasDateRange
{
    protected ?string $defaultDateRange = '7d';

    private function getDateRange(): DateRange
    {
        $preset = $this->defaultDateRange ?? config('glimpse.widget.default_range', '7d');

        return DateRange::fromPreset($preset);
    }
}
