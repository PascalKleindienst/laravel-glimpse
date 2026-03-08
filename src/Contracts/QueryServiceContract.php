<?php

declare(strict_types=1);

namespace LaravelGlimpse\Contracts;

use Illuminate\Support\Collection;
use LaravelGlimpse\Values\DateRange;

/**
 * QueryService
 *
 * A clean read-side API over glimpse_aggregates. Every dashboard
 * Livewire component calls methods on this class — it never touches
 * raw tables directly.
 *
 * All methods accept a DateRange value object so callers don't have to
 * think about period/hour logic themselves.
 */
interface QueryServiceContract
{
    /**
     * @return array{
     *   visitors: int,
     *   page_views: int,
     *   sessions: int,
     *   bounce_rate: float,
     *   avg_duration: float,
     * }
     */
    public function summary(DateRange $range): array;

    /**
     * Returns visitors / page_views grouped by hour (for ≤2 day ranges)
     * or by day (for longer ranges), ready to feed into Chart.js.
     *
     * @return Collection<int, array{label: string, visitors: int, page_views: int}>
     */
    public function timeSeries(DateRange $range): Collection;

    /**
     * @return Collection<int, array{path: string, views: int}>
     */
    public function topPages(DateRange $range, int $limit = 20): Collection;

    /**
     * @return Collection<int, array{channel: string, visitors: int}>
     */
    public function topChannels(DateRange $range, int $limit = 10): Collection;

    /**
     * @return Collection<int, array{domain: string, visitors: int}>
     */
    public function topReferrers(DateRange $range, int $limit = 20): Collection;

    /**
     * @return Collection<int, array{country_code: string, visitors: int}>
     */
    public function topCountries(DateRange $range, int $limit = 20): Collection;

    /**
     * @return Collection<int, array{city: string, visitors: int}>
     */
    public function topCities(DateRange $range, int $limit = 20): Collection;

    /**
     * @return Collection<int, array{language: string, visitors: int}>
     */
    public function topLanguages(DateRange $range, int $limit = 10): Collection;

    /**
     * @return Collection<int, array{browser: string, visitors: int}>
     */
    public function topBrowsers(DateRange $range, int $limit = 10): Collection;

    /**
     * @return Collection<int, array{os: string, visitors: int}>
     */
    public function topOs(DateRange $range, int $limit = 10): Collection;

    /**
     * @return Collection<int, array{platform: string, visitors: int, percentage: float}>
     */
    public function platformBreakdown(DateRange $range): Collection;

    /**
     * @return Collection<int, array{event: string, count: int}>
     */
    public function topEvents(DateRange $range, int $limit = 20): Collection;

    /**
     * Returns the same summary stats for the previous equivalent window,
     * so the dashboard can show "↑ 12% vs last period".
     *
     * @return array{
     *    visitors: int,
     *    page_views: int,
     *    sessions: int,
     *    bounce_rate: float,
     *    avg_duration: float,
     *  }
     */
    public function previousPeriodSummary(DateRange $range): array;
}
