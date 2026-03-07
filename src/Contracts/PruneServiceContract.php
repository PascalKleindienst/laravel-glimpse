<?php

declare(strict_types=1);

namespace LaravelGlimpse\Contracts;

use Illuminate\Database\Eloquent\Builder;
use LaravelGlimpse\Models\GlimpseAggregate;
use LaravelGlimpse\Models\GlimpseEvent;
use LaravelGlimpse\Models\GlimpsePageView;
use LaravelGlimpse\Models\GlimpseSession;

interface PruneServiceContract
{
    /**
     * Delete raw rows older than the configured retention window.
     * Aggregates are only pruned if a retention limit is explicitly set
     * (null = keep forever).
     *
     * Returns a summary array for logging.
     *
     * @return array{sessions: int, page_views: int, events: int, aggregates: int}
     */
    public function prune(): array;

    /**
     * @return Builder<GlimpsePageView>
     */
    public function prunablePageViews(): Builder;

    /**
     * @return Builder<GlimpseEvent>
     */
    public function prunableEvents(): Builder;

    /**
     * @return Builder<GlimpseSession>
     */
    public function prunableSessions(): Builder;

    /**
     * @return Builder<GlimpseAggregate>
     */
    public function prunableAggregates(): Builder;
}
