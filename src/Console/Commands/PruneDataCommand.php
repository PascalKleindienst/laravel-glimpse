<?php

declare(strict_types=1);

namespace LaravelGlimpse\Console\Commands;

use Illuminate\Console\Command;
use LaravelGlimpse\Contracts\PruneServiceContract;

final class PruneDataCommand extends Command
{
    protected $signature = 'glimpse:prune
        {--dry-run : Show what would be deleted without actually deleting anything.}';

    protected $description = 'Delete old raw tracking data according to the retention configuration.';

    public function handle(PruneServiceContract $prune): int
    {
        $rawDays = config('glimpse.retention.raw', 90);
        $aggDays = config('glimpse.retention.aggregates');

        $this->components->info("Pruning raw data older than {$rawDays} days…");
        $this->components->info($aggDays !== null
            ? "Pruning aggregate data older than {$aggDays} days…"
            : 'Aggregate data retention: forever (not pruning).'
        );

        if ($this->option('dry-run')) {
            $this->components->warn('Dry run — no data will be deleted.');

            $this->components->twoColumnDetail('Sessions deleted', (string) $prune->prunableSessions()->count());
            $this->components->twoColumnDetail('Page views deleted', (string) $prune->prunablePageViews()->count());
            $this->components->twoColumnDetail('Events deleted', (string) $prune->prunableEvents()->count());
            $this->components->twoColumnDetail('Aggregates deleted', (string) $prune->prunableAggregates()->count());

            return self::SUCCESS;
        }

        $counts = $prune->prune();

        $this->components->twoColumnDetail('Sessions deleted', (string) $counts['sessions']);
        $this->components->twoColumnDetail('Page views deleted', (string) $counts['page_views']);
        $this->components->twoColumnDetail('Events deleted', (string) $counts['events']);
        $this->components->twoColumnDetail('Aggregates deleted', (string) $counts['aggregates']);

        $this->components->info('Pruning complete.');

        return self::SUCCESS;
    }
}
