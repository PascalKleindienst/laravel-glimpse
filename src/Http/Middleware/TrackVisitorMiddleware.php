<?php

declare(strict_types=1);

namespace LaravelGlimpse\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use LaravelGlimpse\Data\VisitData;
use LaravelGlimpse\Jobs\ProcessVisitJob;
use LaravelGlimpse\Resolvers\DeviceResolver;
use LaravelGlimpse\Services\SessionTrackerService;

use function in_array;

final readonly class TrackVisitorMiddleware
{
    public function __construct(private SessionTrackerService $tracker, private DeviceResolver $device) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Let the request through immediately — tracking happens after the response.
        $response = $next($request);

        // Kick off tracking without blocking the response.
        $this->track($request);

        return $response;
    }

    private function track(Request $request): void
    {
        if (! $this->shouldTrack($request)) {
            return;
        }

        $sessionHash = $this->tracker->resolveSessionHash($request);
        $isNew = ! $this->tracker->isActive($sessionHash);

        // Dispatch to the queue — zero DB writes in the request lifecycle.
        dispatch(new ProcessVisitJob(VisitData::from($request)))
            ->onConnection(config('glimpse.queue_connection'))
            ->onQueue(config('glimpse.queue'));

        // If it's a new session, warm the cache immediately (synchronously)
        // so the very next request within the timeout window is correctly
        // identified as a returning hit rather than a fresh session.
        // This is a lightweight cache write — no DB involved.
        if ($isNew) {
            Cache::put(
                "glimpse:session:{$sessionHash}",
                'pending',
                now()->addMinutes(config('glimpse.session_timeout', 30))
            );
        }
    }

    private function shouldTrack(Request $request): bool
    {
        if (! config('glimpse.enabled', true)) {
            return false;
        }

        // Only track GET requests (ignore AJAX, form posts, API calls, etc.)
        if (! $request->isMethod('GET')) {
            return false;
        }

        // Ignore requests that don't expect HTML (e.g. JSON API responses).
        if ($request->expectsJson()) {
            return false;
        }

        // Exclude configured paths (supports wildcards via fnmatch).
        if ($this->isExcludedPath($request)) {
            return false;
        }

        // Exclude configured IPs.
        if ($this->isExcludedIp($request->ip())) {
            return false;
        }

        // Exclude bots — checked synchronously because we need the result now.
        if (config('glimpse.exclude.bots', true) && $this->device->isBot($request)) {
            return false;
        }

        // Session must be started for hash derivation.
        return $request->hasSession() && $request->session()->isStarted();
    }

    private function isExcludedPath(Request $request): bool
    {
        $path = $request->path();
        $excluded = config('glimpse.exclude.paths', []);

        foreach ($excluded as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    private function isExcludedIp(?string $ip): bool
    {
        if (! $ip) {
            return false;
        }

        return in_array($ip, config('glimpse.exclude.ips', []), strict: true);
    }
}
