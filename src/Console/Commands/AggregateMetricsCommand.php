<?php

declare(strict_types=1);

namespace LaravelGlimpse\Console\Commands;

use Carbon\CarbonInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use LaravelGlimpse\Contracts\AggregationServiceContract;

final class AggregateMetricsCommand extends Command
{
    /**
     * Cache key that stores the timestamp of the last successful run.
     * Used to compute the default --from window automatically.
     */
    private const string LAST_RUN_KEY = 'glimpse:aggregate:last_run';

    protected $signature = 'glimpse:aggregate
        {--from= : Start of the aggregation window (Y-m-d H:i:s). Defaults to last run time.}
        {--to=   : End of the aggregation windoe (Y-m-d H:i:s). Defaults to now.}
        {--force : Re-aggregate even if data was recently processed}';

    protected $description = 'Roll up raw Glimpse tracking data into pre-computed aggregate buckets.';

    public function handle(AggregationServiceContract $aggregator): int
    {
        if (! config('glimpse.enabled', true)) {
            $this->components->warn('Glimpse is disabled. Skipping aggregation.');

            return self::SUCCESS;
        }

        [$from, $to] = $this->resolveWindow();

        if ($from->gte($to)) {
            $this->components->warn('Nothing to aggregate: the time window is empty.');

            return self::SUCCESS;
        }

        $this->components->info(
            "Aggregating Glimpse data from {$from->toDateTimeString()} to {$to->toDateTimeString()}"
        );

        $result = $aggregator->aggregate($from, $to);

        // Persist last-run marker so the next scheduled invocation knows
        // exactly where to resume — no gaps, no double-counting.
        Cache::forever(self::LAST_RUN_KEY, $to->toDateTimeString());

        $this->components->twoColumnDetail('Sessions processed', (string) $result->sessionsProcessed);
        $this->components->twoColumnDetail('Page views processed', (string) $result->pageViewsProcessed);
        $this->components->twoColumnDetail('Events processed', (string) $result->eventsProcessed);
        $this->components->twoColumnDetail('Duration', $result->duration.'ms');

        $this->components->info('Aggregation complete.');

        return self::SUCCESS;
    }

    /**
     * @return array{CarbonInterface, CarbonInterface}
     */
    private function resolveWindow(): array
    {
        $to = $this->option('to')
            ? Date::parse($this->option('to'))
            : Date::now();

        if ($this->option('from')) {
            $from = Date::parse($this->option('from'));
        } else {
            // Use the cached last-run timestamp if available.
            // Fall back to 6 hours ago so the very first run is never empty.
            $lastRun = Cache::get(self::LAST_RUN_KEY);
            $from = $lastRun ? Date::parse($lastRun) : $to->copy()->subHours(6);
        }

        return [$from, $to];
    }
}
