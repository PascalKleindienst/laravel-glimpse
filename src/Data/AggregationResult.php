<?php

declare(strict_types=1);

namespace LaravelGlimpse\Data;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Facades\Context;
use Stringable;

use function sprintf;

/**
 * @implements Arrayable<string, mixed>
 */
final readonly class AggregationResult implements Arrayable, Stringable
{
    public const string CONTEXT_SESSIONS = 'glimpse.aggregates.sessions';

    public const string CONTEXT_PAGE_VIEWS = 'glimpse.aggregates.page_views';

    public const string CONTEXT_EVENTS = 'glimpse.aggregates.events';

    public function __construct(
        public CarbonInterface $from,
        public CarbonInterface $to,
        public int $sessionsProcessed = 0,
        public int $pageViewsProcessed = 0,
        public int $eventsProcessed = 0,
        public float $duration = 0,
    ) {}

    public function __toString(): string
    {
        return sprintf(
            'Aggregated %s → %s | sessions=%d page_views=%d events=%d [%.2fms]',
            $this->from->toDateTimeString(),
            $this->to->toDateTimeString(),
            $this->sessionsProcessed,
            $this->pageViewsProcessed,
            $this->eventsProcessed,
            $this->duration,
        );
    }

    public static function from(CarbonInterface $from, CarbonInterface $to, float $duration = 0): self
    {
        return new self(
            from: $from,
            to: $to,
            sessionsProcessed: Context::get(self::CONTEXT_SESSIONS, 0),
            pageViewsProcessed: Context::get(self::CONTEXT_PAGE_VIEWS, 0),
            eventsProcessed: Context::get(self::CONTEXT_EVENTS, 0),
            duration: $duration,
        );
    }

    public function summary(): string
    {
        return $this->__toString();
    }

    /**
     * @return array{
     *   sessionsProcessed: int,
     *   pageViewsProcessed: int,
     *   eventsProcessed: int,
     *   duration: float,
     *   from: string,
     *   to: string,
     * }
     */
    public function toArray(): array
    {
        return [
            'sessionsProcessed' => $this->sessionsProcessed,
            'pageViewsProcessed' => $this->pageViewsProcessed,
            'eventsProcessed' => $this->eventsProcessed,
            'duration' => $this->duration,
            'from' => $this->from->toDateTimeString(),
            'to' => $this->to->toDateTimeString(),
        ];
    }
}
