<?php

declare(strict_types=1);

namespace LaravelGlimpse\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use LaravelGlimpse\Contracts\QueryServiceContract;
use LaravelGlimpse\Enums\Platform;
use LaravelGlimpse\Models\GlimpseAggregate;
use LaravelGlimpse\Values\Country;
use LaravelGlimpse\Values\DateRange;
use LaravelGlimpse\Values\Os;

final readonly class QueryService implements QueryServiceContract
{
    public function timeSeries(DateRange $range): Collection
    {
        $period = $this->choosePeriod($range);

        $rows = GlimpseAggregate::query()
            ->where('period', $period)
            ->whereBetween('date', [$range->from->toDateTimeString(), $range->to->toDateTimeString()])
            ->where('dimension', '-')
            ->whereIn('metric', ['visitors', 'page_views'])
            ->get(['period', 'date', 'hour', 'metric', 'value', 'count']);

        // Group by bucket key → [label, visitors, page_views]
        $buckets = collect();

        foreach ($this->generateBuckets($range, $period) as $key => $label) {
            $bucketRows = $rows->filter(fn (GlimpseAggregate $r): bool => $this->bucketKey($r) === $key);

            $buckets->push([
                'label' => $label,
                'visitors' => (int) ($bucketRows->firstWhere('metric', 'visitors')->count ?? 0),
                'page_views' => (int) ($bucketRows->firstWhere('metric', 'page_views')->count ?? 0),
            ]);
        }

        return $buckets;
    }

    public function topPages(DateRange $range, int $limit = 20): Collection
    {
        return GlimpseAggregate::query()
            ->where('period', $this->choosePeriod($range))
            ->whereBetween('date', [$range->from->toDateTimeString(), $range->to->toDateTimeString()])
            ->where('metric', 'page_views')
            ->where('dimension', 'like', 'path:%')
            ->selectRaw('dimension, SUM(`count`) as total')
            ->groupBy('dimension')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn (GlimpseAggregate $row): array => [
                'path' => Str::substr($row->dimension ?? '', 5), // strip 'path:'
                'views' => (int) $row->total, // @phpstan-ignore-line
            ]);
    }

    public function topChannels(DateRange $range, int $limit = 10): Collection
    {
        return $this->topDimension($range, 'visitors', 'channel', $limit)
            ->map(fn (array $row): array => ['channel' => $row['dimension'], 'visitors' => $row['count']]);
    }

    public function topReferrers(DateRange $range, int $limit = 20): Collection
    {
        return $this->topDimension($range, 'visitors', 'referrer', $limit)
            ->map(fn (array $row): array => ['domain' => $row['dimension'], 'visitors' => $row['count']]);
    }

    public function topCountries(DateRange $range, int $limit = 20): Collection
    {
        return $this->topDimension($range, 'visitors', 'country', $limit)
            ->map(fn (array $row): array => ['country' => Country::fromIso($row['dimension']), 'visitors' => $row['count']]);
    }

    public function topCities(DateRange $range, int $limit = 20): Collection
    {
        return $this->topDimension($range, 'visitors', 'city', $limit)
            ->map(fn (array $row): array => ['city' => $row['dimension'], 'visitors' => $row['count']]);
    }

    public function topLanguages(DateRange $range, int $limit = 10): Collection
    {
        return $this->topDimension($range, 'visitors', 'language', $limit)
            ->map(fn (array $row): array => ['language' => $row['dimension'], 'visitors' => $row['count']]);
    }

    public function topBrowsers(DateRange $range, int $limit = 10): Collection
    {
        return $this->topDimension($range, 'visitors', 'browser', $limit)
            ->map(fn (array $row): array => ['browser' => $row['dimension'], 'visitors' => $row['count']]);
    }

    public function topOs(DateRange $range, int $limit = 10): Collection
    {
        return $this->topDimension($range, 'visitors', 'os', $limit)
            ->map(fn (array $row): array => ['os' => Os::from($row['dimension']), 'visitors' => $row['count']]);
    }

    public function platformBreakdown(DateRange $range): Collection
    {
        $rows = $this->topDimension($range, 'visitors', 'platform', 10);
        $total = $rows->sum('count');

        return $rows->map(fn (array $row): array => [
            'platform' => Platform::from($row['dimension']),
            'visitors' => $row['count'],
            'percentage' => $total > 0 ? round(($row['count'] / $total) * 100, 1) : 0.0,
        ]);
    }

    public function topEvents(DateRange $range, int $limit = 20): Collection
    {
        return GlimpseAggregate::query()
            ->where('period', $this->choosePeriod($range))
            ->whereBetween('date', [$range->from->toDateTimeString(), $range->to->toDateTimeString()])
            ->where('metric', 'events')
            ->where('dimension', 'like', 'event:%')
            ->selectRaw('dimension, SUM(`count`) as total')
            ->groupBy('dimension')
            ->orderByDesc('total')
            ->limit($limit)
            ->get()
            ->map(fn (GlimpseAggregate $row): array => [
                'event' => mb_substr((string) $row->dimension, 6), // strip 'event:'
                'count' => (int) $row->total, // @phpstan-ignore-line
            ]);
    }

    public function previousPeriodSummary(DateRange $range): array
    {
        return $this->summary($range->previousPeriod());
    }

    public function summary(DateRange $range): array
    {
        $period = $this->choosePeriod($range);
        $rows = GlimpseAggregate::query()
            ->selectRaw('metric, SUM(`count`) as count, AVG(`value`) as value')
            ->where('period', $period)
            ->whereBetween('date', [$range->from->toDateTimeString(), $range->to->toDateTimeString()])
            ->where('dimension', '-')
            ->groupBy('metric')
            // TODO: enum???
            ->whereIn('metric', ['visitors', 'page_views', 'bounce_rate', 'avg_duration', 'avg_time_on_page'])
            ->get(['metric', 'value', 'count', 'date']);
        $byMetric = $rows->keyBy('metric');

        return [
            'visitors' => (int) ($byMetric->get('visitors')->count ?? 0),
            'page_views' => (int) ($byMetric->get('page_views')->count ?? 0),
            'sessions' => (int) ($byMetric->get('visitors')->count ?? 0),
            'bounce_rate' => (float) ($byMetric->get('bounce_rate')->value ?? 0),
            'avg_duration' => (float) ($byMetric->get('avg_duration')->value ?? 0),
            'avg_time_on_page' => (float) ($byMetric->get('avg_time_on_page')->value ?? 0),
        ];
    }

    /**
     * For ranges ≤1 days we use hourly granularity; otherwise daily.
     */
    private function choosePeriod(DateRange $range): string
    {
        return $range->diffInDays() <= 1 ? 'hourly' : 'daily';
    }

    /**
     * Generate all time bucket keys and their human-readable labels
     * for the given date range and period granularity.
     *
     * @return array<string, string> [bucketKey => displayLabel]
     */
    private function generateBuckets(DateRange $range, string $period): array
    {
        $buckets = [];
        $cursor = $range->from->copy();

        if ($period === 'hourly') {
            $cursor->startOfHour();
            while ($cursor->lte($range->to)) {
                $key = $cursor->toDateString().':'.$cursor->hour;
                $buckets[$key] = $cursor->format('j M H:i');
                $cursor->addHour();
            }
        } else {
            $cursor->startOfDay();
            while ($cursor->lte($range->to)) {
                $key = $cursor->toDateString().':null';
                $buckets[$key] = $cursor->format('j M');
                $cursor->addDay();
            }
        }

        return $buckets;
    }

    private function bucketKey(GlimpseAggregate $row): string
    {
        return $row->date->toDateString().':'.($row->hour >= 0 ? $row->hour : 'null');
    }

    /**
     * @return Collection<array-key, array{dimension: string, count: int}>
     */
    private function topDimension(DateRange $range, string $metric, string $dimensionPrefix, int $limit): Collection
    {
        return GlimpseAggregate::query()
            ->where('period', $this->choosePeriod($range))
            ->whereBetween('date', [$range->from->toDateTimeString(), $range->to->toDateTimeString()])
            ->where('metric', $metric)
            ->where('dimension', 'like', "{$dimensionPrefix}:%")
            ->selectRaw('SUBSTRING(dimension, ?) as dimension, SUM(`count`) as total_count', [Str::length($dimensionPrefix) + 2])
            ->groupBy('dimension')
            ->orderByDesc('total_count')
            ->limit($limit)
            ->get()
            ->map(fn (GlimpseAggregate $row): array => [
                'dimension' => $row->dimension,
                'count' => (int) $row->total_count, // @phpstan-ignore-line
            ]);
    }
}
