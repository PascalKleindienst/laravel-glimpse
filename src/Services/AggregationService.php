<?php

declare(strict_types=1);

namespace LaravelGlimpse\Services;

use BackedEnum;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use LaravelGlimpse\Contracts\AggregationServiceContract;
use LaravelGlimpse\Data\AggregationResult;
use LaravelGlimpse\Models\GlimpseAggregate;
use LaravelGlimpse\Models\GlimpseEvent;
use LaravelGlimpse\Models\GlimpsePageView;
use LaravelGlimpse\Models\GlimpseSession;
use LaravelGlimpse\Values\AggregationPeriod;

final readonly class AggregationService implements AggregationServiceContract
{
    public function aggregate(Carbon $from, Carbon $to): AggregationResult
    {
        $startedAt = microtime(true);

        // We aggregate hourly first; daily totals are then derived from
        // hourly buckets so math is consistent.
        $this->aggregateHourly($from, $to);
        $this->aggregateDaily($from, $to);

        return AggregationResult::from($from->toImmutable(), $to->toImmutable(), round((microtime(true) - $startedAt) * 1000, 2));
    }

    /**
     * Generate every hour bucket in the window so we always have a row
     * even for hours with zero traffic (makes charting easier).
     */
    private function aggregateHourly(Carbon $from, Carbon $to): void
    {
        $cursor = $from->copy()->startOfHour();

        while ($cursor->lte($to)) {
            $period = AggregationPeriod::hourly($cursor, $cursor->copy()->endOfHour());

            // Use transactions to reduce fsync lag and speed up import
            DB::transaction(function () use ($period): void {
                $this->aggregateSessionsForBucket($period);
                $this->aggregatePageViewsForBucket($period);
                $this->aggregateEventsForBucket($period);
            });

            $cursor->addHour();
        }
    }

    private function aggregateSessionsForBucket(AggregationPeriod $period): void
    {
        // ---- Top-level counters ----
        $sessionStats = GlimpseSession::query()
            ->whereBetween('started_at', [$period->start, $period->end])
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN is_bounce = 1 THEN 1 ELSE 0 END) as bounces,
                SUM(duration_seconds) as duration_sum,
                AVG(duration_seconds) as avg_duration
            ')
            ->first();

        $total = (int) ($sessionStats->total ?? 0);
        $bounces = (int) ($sessionStats->bounces ?? 0);

        // $this->upsert($period, 'visitors', null, $total, $total);
        $this->batchUpsert([
            $this->bucket($period, $total, 'visitors'),
            $this->bucket($period, $total, 'sessions'),
            $this->bucket($period, $total, 'bounces', value: $bounces),
            $this->bucket($period, $total, 'bounce_rate', value: $total > 0 ? round(($bounces / $total) * 100, 2) : 0),
            $this->bucket($period, $total, 'duration_sum', value: (float) ($sessionStats->duration_sum ?? 0)),
            $this->bucket($period, $total, 'avg_duration', value: (float) ($sessionStats->avg_duration ?? 0)),
        ]);

        Context::increment(AggregationResult::CONTEXT_SESSIONS, $total);

        if ($total === 0) {
            return;
        }

        // Aggregate Dimensions and referrers
        $this->aggregateDimensionsForBucket($period);
        $this->aggregateReferrersForBucket($period);
    }

    /**
     * @param  array<array{period: string, date: string, hour: ?int, metric: string, dimension: ?string, value: float, count: int, aggregated_at: Carbon}>  $data
     */
    private function batchUpsert(array $data): void
    {
        // ! TBD: Upsert does not work reliably with sqlite, so we use the slower version and insert each query by itself...
        // foreach ($data as $row) {
        //     GlimpseAggregate::query()->updateOrCreate([
        //         'period' => $row['period'],
        //         'date' => $row['date'],
        //         'hour' => $row['hour'],
        //         'metric' => $row['metric'],
        //         'dimension' => $row['dimension'],
        //     ], [
        //         'value' => $row['value'],
        //         'count' => $row['count'],
        //         'aggregated_at' => $row['aggregated_at'],
        //     ]);
        // }

        if ($data !== []) {
            GlimpseAggregate::query()->upsert(
                $data,
                uniqueBy: ['period', 'date', 'hour', 'metric', 'dimension'],
                update: ['value', 'count', 'aggregated_at']
            );
        }
    }

    /**
     * Insert or update a single aggregate row.
     * Uses updateOrCreate so the method is fully idempotent.
     */
    private function upsert(
        AggregationPeriod $period,
        string $metric,
        ?string $dimension,
        float $value,
        int $count
    ): void {
        $bucket = $this->bucket($period, $count, $metric, $value, $dimension);

        GlimpseAggregate::query()->updateOrCreate([
            'period' => $bucket['period'],
            'date' => $bucket['date'],
            'hour' => $bucket['hour'],
            'metric' => $bucket['metric'],
            'dimension' => $bucket['dimension'],
        ], [
            'value' => $bucket['value'],
            'count' => $bucket['count'],
            'aggregated_at' => $bucket['aggregated_at'],
        ]);
    }

    /**
     * @return array{period: string, date: string, hour: ?int, metric: string, dimension: string, value: float, count: int, aggregated_at: Carbon}
     */
    private function bucket(AggregationPeriod $period, int $count, string $metric, ?float $value = null, ?string $dimension = null): array
    {
        return [
            'period' => $period->period,
            'date' => $period->start->toDateTimeString(),
            'hour' => $period->period === 'hourly' ? $period->start->hour : -1,
            'metric' => $metric,
            'dimension' => $dimension ?? '-',
            'value' => $value ?? (float) $count,
            'count' => $count,
            'aggregated_at' => Date::now(),
        ];
    }

    private function aggregateDimensionsForBucket(AggregationPeriod $period): void
    {
        // We define each breakdown as [column => metric_prefix].
        $dimensionMap = [
            'country_code' => 'country',
            'city' => 'city',
            'browser' => 'browser',
            'os' => 'os',
            'platform' => 'platform',
            'referrer_channel' => 'channel',
            'language' => 'language',
        ];

        foreach ($dimensionMap as $column => $dimensionPrefix) {
            /** @var Collection<string, GlimpseSession&object{cnt: int, bounces: int}> $rows */
            $rows = GlimpseSession::query()
                ->whereBetween('started_at', [$period->start, $period->end])
                ->whereNotNull($column)
                ->groupBy($column)
                ->selectRaw("{$column}, COUNT(*) as cnt, SUM(CASE WHEN is_bounce = 1 THEN 1 ELSE 0 END) as bounces")
                ->get();

            $batches = [];
            foreach ($rows as $row) {
                $value = $row->$column ?? null;

                if ($value instanceof BackedEnum) {
                    $value = $value->value;
                }

                $dim = "{$dimensionPrefix}:{$value}";
                $count = (int) $row->cnt;

                $batches[] = $this->bucket($period, $count, 'visitors', dimension: $dim);

                // Per-dimension bounce rate
                $dimBounceRate = $count > 0
                    ? round(($row->bounces / $count) * 100, 2)
                    : 0;

                $batches[] = $this->bucket($period, $count, 'bounce_rate', $dimBounceRate, $dim);
            }

            $this->batchUpsert($batches);
        }
    }

    /**
     * Referrer domain breakdown (separate from channel)
     */
    private function aggregateReferrersForBucket(AggregationPeriod $period): void
    {
        /** @var Collection<string, GlimpseSession&object{cnt: int}> $referrerRows */
        $referrerRows = GlimpseSession::query()
            ->whereBetween('started_at', [$period->start, $period->end])
            ->whereNotNull('referrer_domain')
            ->groupBy('referrer_domain')
            ->selectRaw('referrer_domain, COUNT(*) as cnt')
            ->get();

        $batches = $referrerRows->map(fn (/** @var (GlimpseSession&object{cnt: int}) $row */ GlimpseSession $row): array => $this->bucket(
            $period,
            count: (int) $row->cnt,
            metric: 'visitors',
            dimension: "referrer:{$row->referrer_domain}"
        ));
        $this->batchUpsert($batches->all());
    }

    private function aggregatePageViewsForBucket(AggregationPeriod $period): void
    {
        // Get Total Views
        $total = GlimpsePageView::query()
            ->whereBetween('created_at', [$period->start, $period->end])
            ->count();

        $this->upsert($period, 'page_views', null, $total, $total);
        Context::increment(AggregationResult::CONTEXT_PAGE_VIEWS, $total);

        if ($total === 0) {
            return;
        }

        // Get Top pages
        /** @var Collection<string, (GlimpsePageView&object{cnt: int})> $pageViews */
        $pageViews = GlimpsePageView::query()
            ->whereBetween('created_at', [$period->start, $period->end])
            ->whereNotNull('path')
            ->groupBy('path')
            ->selectRaw('path, COUNT(*) as cnt')
            ->orderByDesc('cnt')
            ->limit(100) // store top 100 paths per bucket
            ->get();

        $batches = $pageViews->map(fn (/** @var (GlimpsePageView&object{cnt: int}) $row */ GlimpsePageView $row): array => $this->bucket(
            $period,
            count: (int) $row->cnt,
            metric: 'page_views',
            dimension: "path:{$row->path}"
        ));
        $this->batchUpsert($batches->all());

        // Avg time on page (excluding nulls — last page in session)
        $avgTimeOnPage = GlimpsePageView::query()
            ->whereBetween('created_at', [$period->start, $period->end])
            ->whereNotNull('time_on_page_seconds')
            ->avg('time_on_page_seconds') ?? 0;

        $this->upsert($period, 'avg_time_on_page', null, round((float) $avgTimeOnPage, 2), $total);
    }

    private function aggregateEventsForBucket(AggregationPeriod $period): void
    {
        /** @var Collection<string, GlimpseEvent&object{cnt: int}> $rows */
        $rows = GlimpseEvent::query()
            ->whereBetween('created_at', [$period->start, $period->end])
            ->groupBy('name')
            ->selectRaw('name, COUNT(*) as cnt')
            ->get();

        foreach ($rows as $row) {
            $this->upsert(
                $period,
                'events',
                "event:{$row->name}",
                (int) $row->cnt, (int) $row->cnt
            );
            Context::increment(AggregationResult::CONTEXT_EVENTS, (int) $row->cnt);
        }

        // Total events (no dimension) for summary stat
        $total = $rows->sum('cnt');
        if ($total > 0) {
            $this->upsert($period, 'events', null, (int) $total, (int) $total);
        }
    }

    private function aggregateDaily(Carbon $from, Carbon $to): void
    {
        $cursor = $from->copy()->startOfDay();

        while ($cursor->lte($to)) {
            $period = AggregationPeriod::daily($cursor, $cursor->copy()->endOfHour());

            // Use transactions to reduce fsync lag and speed up import
            DB::transaction(function () use ($period): void {
                $this->aggregateSessionsForBucket($period);
                $this->aggregatePageViewsForBucket($period);
                $this->aggregateEventsForBucket($period);
            });

            $cursor->addDay();
        }
    }
}
