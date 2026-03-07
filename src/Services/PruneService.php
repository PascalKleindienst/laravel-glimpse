<?php

declare(strict_types=1);

namespace LaravelGlimpse\Services;

use Illuminate\Container\Attributes\Config;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Date;
use InvalidArgumentException;
use LaravelGlimpse\Contracts\PruneServiceContract;
use LaravelGlimpse\Models\GlimpseAggregate;
use LaravelGlimpse\Models\GlimpseEvent;
use LaravelGlimpse\Models\GlimpsePageView;
use LaravelGlimpse\Models\GlimpseSession;
use Throwable;

final readonly class PruneService implements PruneServiceContract
{
    public function __construct(
        #[Config('glimpse.retention.raw', 90)] public int $retentionDays,
        #[Config('glimpse.retention.aggregates', null)] public ?int $retentionAggregateDays,
    ) {}

    public function prune(): array
    {
        // Delete in dependency order: page_views and events before sessions
        // (foreign key constraints).
        $pageViews = $this->prunablePageViews()->delete();
        $events = $this->prunableEvents()->delete();

        // Sessions without any remaining child rows can be safely deleted.
        // We delete by started_at to keep the logic simple and predictable.
        $sessions = $this->prunableSessions()->delete();

        $aggregates = 0;
        if ($this->retentionAggregateDays !== null) {
            $aggregates = $this->prunableAggregates()->delete();
        }

        return [
            'sessions' => $sessions,
            'page_views' => $pageViews,
            'events' => $events,
            'aggregates' => $aggregates,
        ];
    }

    /**
     * @return Builder<GlimpsePageView>
     */
    public function prunablePageViews(): Builder
    {
        return GlimpsePageView::query()
            ->where('created_at', '<', Date::now()->subDays($this->retentionDays));
    }

    /**
     * @return Builder<GlimpseEvent>
     */
    public function prunableEvents(): Builder
    {
        return GlimpseEvent::query()
            ->where('created_at', '<', Date::now()->subDays($this->retentionDays));
    }

    /**
     * @return Builder<GlimpseSession>
     */
    public function prunableSessions(): Builder
    {
        return GlimpseSession::query()->where('started_at', '<', Date::now()->subDays($this->retentionDays));
    }

    /**
     * @return Builder<GlimpseAggregate>
     *
     * @throws Throwable|InvalidArgumentException if aggregate retention days are null
     */
    public function prunableAggregates(): Builder
    {
        throw_if($this->retentionAggregateDays === null, InvalidArgumentException::class, 'Aggregate Retention Days should not be null');

        return GlimpseAggregate::query()
            ->where('date', '<', Date::now()->subDays($this->retentionAggregateDays));
    }
}
