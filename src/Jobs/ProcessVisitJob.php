<?php

declare(strict_types=1);

namespace LaravelGlimpse\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use LaravelGlimpse\Contracts\Resolver;
use LaravelGlimpse\Data\VisitData;
use LaravelGlimpse\Models\GlimpseSession;
use LaravelGlimpse\Services\SessionTrackerService;

final class ProcessVisitJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Maximum retry attempts before discarding the job.
     * Analytics data is non-critical, so we fail fast.
     */
    public int $tries = 3;

    public function __construct(private readonly VisitData $visit) {}

    public function handle(
        SessionTrackerService $tracker
    ): void {

        if ($this->visit->isNewSession) {
            $this->handleNewSession($tracker);

            return;
        }

        $this->handleSubsequentHit($tracker);
    }

    private function handleNewSession(SessionTrackerService $tracker): void
    {
        // Reconstruct a minimal fake request so our resolvers (which accept
        // Illuminate\Http\Request) can work without needing the real request.
        $request = $this->visit->toRequest();

        $data = collect(config('glimpse.resolver', [])) // @phpstan-ignore-line
            ->keys()
            ->map(fn (string $class) => app($class))
            ->filter(fn (object $instance): bool => $instance instanceof Resolver)
            ->flatMap(fn (Resolver $resolver): array => $resolver->resolve($request));

        // Don't record bots (belt-and-suspenders check; middleware already blocks them).
        if ($data['is_bot'] ?? false) {
            return;
        }

        $tracker->createSession([
            'session_hash' => $this->visit->sessionHash,
            'ip_hash' => $this->visit->ipHash,
            'entry_page' => $this->visit->path,
            'exit_page' => $this->visit->path,
            'page_view_count' => 1,
            'duration_seconds' => 0,
            'is_bounce' => true,
            'started_at' => $this->visit->hitAt,
            'last_seen_at' => $this->visit->hitAt,
            ...$data->except(['is_bot'])->toArray(),
        ]);

        $tracker->recordPageView($this->visit->sessionHash, $request, $this->visit->hitAt);
    }

    private function handleSubsequentHit(SessionTrackerService $tracker): void
    {
        $session = GlimpseSession::query()->where('session_hash', $this->visit->sessionHash)->first();

        if (! $session) {
            // Session was pruned between the middleware check and job execution.
            // Treat as a new session on the next hit; nothing to do here.
            return;
        }

        $request = $this->visit->toRequest();
        $tracker->updateSession($session, $this->visit->path, $this->visit->hitAt);
        $tracker->recordPageView($this->visit->sessionHash, $request, $this->visit->hitAt);
    }
}
