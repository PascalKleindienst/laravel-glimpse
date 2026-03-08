<?php

declare(strict_types=1);

namespace LaravelGlimpse\Console\Commands;

use Carbon\Carbon;
use Generator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Date;
use LaravelGlimpse\Contracts\AggregationServiceContract;
use LaravelGlimpse\Data\AggregationResult;

final class BackfillDataCommand extends Command
{
    protected $signature = 'glimpse:backfill
        {--from=      : Start date (Y-m-d). Defaults to 90 days ago.}
        {--to=        : End date (Y-m-d). Defaults to today.}
        {--days=      : Shorthand for "backfill the last N days".}
        {--chunk=7    : Process this many days per iteration to keep memory bounded.}';

    protected $description = 'Re-aggregate metric data for a historical date range';

    public function handle(AggregationServiceContract $aggregator): int
    {
        [$from, $to] = $this->resolveRange();

        $chunkDays = max(1, (int) $this->option('chunk'));
        $totalDays = (int) $from->diffInDays($to) + 1;

        $this->components->info(
            "Backfilling {$totalDays} day(s) from {$from->toDateString()} to {$to->toDateString()} "
            ."in {$chunkDays}-day chunks…"
        );

        foreach ($this->buildChunks($from, $to, $chunkDays) as [$chunkFrom, $chunkTo]) {
            $this->components->task("Process $chunkFrom - $chunkTo", fn (): AggregationResult => $aggregator->aggregate($chunkFrom, $chunkTo));
        }

        $this->newLine(2);
        $this->components->info('Backfill complete.');

        return self::SUCCESS;
    }

    /**
     * @return array{Carbon, Carbon}
     */
    private function resolveRange(): array
    {
        if ($days = $this->option('days')) {
            $from = Date::now()->subDays((int) $days - 1)->startOfDay();
            $to = Date::now()->endOfDay();

            return [$from, $to];
        }

        $from = $this->option('from')
            ? Date::parse((string) $this->option('from'))->startOfDay()
            : Date::now()->subDays(89)->startOfDay();

        $to = $this->option('to')
            ? Date::parse((string) $this->option('to'))->endOfDay()
            : Date::now()->endOfDay();

        return [$from, $to];
    }

    /**
     * Yield [$chunkFrom, $chunkTo] pairs iterating through the full range.
     *
     * @return Generator<array{0: Carbon, 1: Carbon}>
     */
    private function buildChunks(Carbon $from, Carbon $to, int $chunkDays): Generator
    {
        $cursor = $from->copy();

        while ($cursor->lte($to)) {
            $chunkEnd = $cursor->copy()->addDays($chunkDays - 1)->endOfDay();

            if ($chunkEnd->gt($to)) {
                $chunkEnd = $to->copy();
            }

            yield [$cursor->copy(), $chunkEnd];

            $cursor->addDays($chunkDays);
        }
    }
}
