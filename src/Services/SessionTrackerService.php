<?php

declare(strict_types=1);

namespace LaravelGlimpse\Services;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use LaravelGlimpse\Models\GlimpsePageView;
use LaravelGlimpse\Models\GlimpseSession;

final readonly class SessionTrackerService
{
    /**
     * How long we keep the session in the cache (minutes).
     * After this window, the next hit will start a new session.
     */
    private int $timeout;

    public function __construct()
    {
        $this->timeout = (int) config('glimpse.session_timeout', 30);
    }

    /**
     * Derive a stable, anonymous session hash from the Laravel session ID.
     * The raw session ID is never stored — only its hash.
     */
    public function resolveSessionHash(Request $request): string
    {
        return hash('sha256', $request->session()->getId());
    }

    /**
     * Hash the visitor's IP address for storage.
     * We append a server-side secret so the hash cannot be reversed
     * by an attacker who knows the IP.
     */
    public function hashIp(string $ip): string
    {
        return hash('sha256', $ip.config('app.key'));
    }

    /**
     * Return the cached session for this hash if it is still within the
     * activity window, or null if the session has expired / never existed.
     */
    public function getCachedSession(string $hash): ?GlimpseSession
    {
        /** @var string|null $id */
        $id = Cache::get($this->cacheKey($hash));

        if (! $id) {
            return null;
        }

        return GlimpseSession::query()->find($id);
    }

    /**
     * Persist a new GlimpseSession to the database and warm the cache.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function createSession(array $attributes): GlimpseSession
    {
        $session = GlimpseSession::query()->create($attributes);

        $this->cacheSession($session);

        return $session;
    }

    /**
     * Update an existing session on a subsequent hit (not first page view).
     * Also backfills the time_on_page_seconds for the previous page view.
     */
    public function updateSession(GlimpseSession $session, string $path, CarbonImmutable $now): void
    {
        // Close the previous page view's time_on_page.
        $previousView = GlimpsePageView::query()->where('session_hash', $session->session_hash)
            ->whereNull('time_on_page_seconds')
            ->latest('created_at')
            ->first();

        $previousView?->closeWithTimestamp($now);

        // Update the session counters.
        $session->recordSubsequentHit($path, $now);

        // Refresh the cache TTL.
        $this->cacheSession($session);
    }

    /**
     * Record a single page view row.
     */
    public function recordPageView(string $sessionHash, Request $request, DateTimeInterface $now): GlimpsePageView
    {
        return GlimpsePageView::query()->create([
            'session_hash' => $sessionHash,
            'url' => $request->fullUrl(),
            'path' => $request->path() === '/' ? '/' : '/'.$request->path(),
            'query_string' => $request->getQueryString(),
            'referrer' => $request->headers->get('referer'),
            'created_at' => $now,
        ]);
    }

    /**
     * Check whether the given session hash is currently "live" in the cache.
     */
    public function isActive(string $hash): bool
    {
        return Cache::has($this->cacheKey($hash));
    }

    private function cacheKey(string $hash): string
    {
        return "glimpse:session:{$hash}";
    }

    private function cacheSession(GlimpseSession $session): void
    {
        Cache::put(
            $this->cacheKey($session->session_hash),
            $session->id,
            now()->addMinutes($this->timeout)
        );
    }
}
