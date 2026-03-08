<?php

declare(strict_types=1);

namespace LaravelGlimpse\Values;

use Carbon\Carbon;
use Stringable;

use function sprintf;

/**
 * Immutable value object representing a date range for aggregating metrics
 *
 * Usage:
 *   AggregationPeriod::hourly($start, $end)
 *   AggregationPeriod::daily($start, $end)
 *   AggregationPeriod::custom($start, $end)
 */
final readonly class AggregationPeriod implements Stringable
{
    private function __construct(public Carbon $start, public Carbon $end, public string $period = 'custom') {}

    public function __toString(): string
    {
        return sprintf('[%s] %s → %s', $this->period, $this->start->toDateTimeString(), $this->end->toDateTimeString());
    }

    public static function custom(Carbon $start, Carbon $end): self
    {
        return new self($start->copy(), $end->copy());
    }

    public static function hourly(Carbon $start, Carbon $end): self
    {
        return new self($start->copy(), $end->copy(), 'hourly');
    }

    public static function daily(Carbon $start, Carbon $end): self
    {
        return new self($start->copy(), $end->copy(), 'daily');
    }
}
