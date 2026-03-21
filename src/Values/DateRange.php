<?php

declare(strict_types=1);

namespace LaravelGlimpse\Values;

use Carbon\Carbon;
use Illuminate\Support\Facades\Date;
use Livewire\Wireable;
use Stringable;

use function sprintf;

/**
 * Immutable value object representing a date range for dashboard queries.
 *
 * Usage:
 *   DateRange::today()
 *   DateRange::last7Days()
 *   DateRange::last30Days()
 *   DateRange::custom($from, $to)
 *   DateRange::fromPreset('7d')
 */
final readonly class DateRange implements Stringable, Wireable
{
    public Carbon $from;

    public Carbon $to;

    private function __construct(Carbon $from, Carbon $to, public string $preset = 'custom')
    {
        $this->from = $from->copy()->startOfDay();
        $this->to = $to->copy()->endOfDay();
    }

    public function __toString(): string
    {
        return sprintf('[%s] %s → %s', $this->preset, $this->from->toDateTimeString(), $this->to->toDateTimeString());
    }

    /**
     * Resolve a DateRange from a short preset string.
     * Unknown presets fall back to last 7 days.
     */
    public static function fromPreset(string $preset): self
    {
        return match ($preset) {
            'today' => self::today(),
            'yesterday' => self::yesterday(),
            '30d' => self::last30Days(),
            '90d' => self::last90Days(),
            'month' => self::thisMonth(),
            default => self::last7Days(),
        };
    }

    public static function today(): self
    {
        return new self(Date::today(), Date::today(), 'today');
    }

    public static function yesterday(): self
    {
        return new self(Date::yesterday(), Date::yesterday(), 'yesterday');
    }

    public static function last30Days(): self
    {
        return new self(Date::now()->subDays(29)->startOfDay(), Date::now(), '30d');
    }

    public static function last90Days(): self
    {
        return new self(Date::now()->subDays(89)->startOfDay(), Date::now(), '90d');
    }

    public static function thisMonth(): self
    {
        return new self(Date::now()->startOfMonth(), Date::now()->endOfMonth(), 'month');
    }

    public static function last7Days(): self
    {
        return new self(Date::now()->subDays(6)->startOfDay(), Date::now(), '7d');
    }

    /**
     * All available presets for the UI picker.
     *
     * @return array<string, string> [preset => label]
     */
    public static function presets(): array
    {
        return [
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            '7d' => 'Last 7 days',
            '30d' => 'Last 30 days',
            '90d' => 'Last 90 days',
            'month' => 'This month',
        ];
    }

    public static function custom(Carbon $from, Carbon $to): self
    {
        return new self($from, $to, 'custom');
    }

    /**
     * @param  array{from: Carbon, to: Carbon, preset: string}  $value
     */
    public static function fromLivewire($value): self // @pest-ignore-type
    {
        return new self($value['from'], $value['to'], $value['preset']);
    }

    /**
     * Return the equivalent previous period (same duration, immediately before).
     */
    public function previousPeriod(): self
    {
        $days = $this->diffInDays() + 1;
        $prevTo = $this->from->copy()->subDay();
        $prevFrom = $prevTo->copy()->subDays($days - 1);

        return self::custom($prevFrom, $prevTo);
    }

    public function diffInDays(): int
    {
        return (int) $this->from->diffInDays($this->to);
    }

    /**
     * Human-readable label for the UI.
     */
    public function label(): string
    {
        return match ($this->preset) {
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            '7d' => 'Last 7 days',
            '30d' => 'Last 30 days',
            '90d' => 'Last 90 days',
            'month' => 'This month',
            default => $this->from->format('M j').' – '.$this->to->format('M j, Y'),
        };
    }

    /**
     * @return array{from: Carbon, to: Carbon, preset: string}
     */
    public function toLivewire(): array
    {
        return [
            'from' => $this->from,
            'to' => $this->to,
            'preset' => $this->preset,
        ];
    }
}
